<?php

namespace App\Services;

use App\Enums\AccountsEnum;
use App\Models\BankAccount;
use App\Models\Installment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

abstract class AccountService
{
    protected string $model; // Ex: AccountPayable::class ou AccountReceivable::class

    public function __construct()
    {
        $this->model = $this->getModel();
    }

    abstract protected function getModel(): string;

    public function findAll($request, string $periodo, Tenant $tenant)
    {
        return $tenant->run(function () use ($request, $periodo) {
            $inicio = $request->query('inicio')
                ? Carbon::parse($request->query('inicio'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();

            $fim = $request->query('fim')
                ? Carbon::parse($request->query('fim'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $periodo)->endOfMonth();

            if ($request->query('status') === 'a-vencer') {
                $inicio = Carbon::today();
                $fim = Carbon::today()->addDays($request->query('dias'));
                $mesAno = Carbon::createFromFormat('Y-m', $periodo);

                if ($mesAno->format('Y-m') !== now()->format('Y-m')) {
                    return false;
                }
            }

            $hoje = null;
            if ($request->query('status') === 'vencem-hoje' || $request->query('status') === 'vencidos') {
                $hoje = now()->startOfDay();
            }

            return $this->model::when($request->query('conta_id') !== null, function (Builder $query) use ($request) {
                $query->whereHas('bankAccount', function (Builder $query) use ($request) {
                    $query->where('bank_account_id', $request->query('conta_id'));
                });
            })
                ->when($request->query('conta_id') === null && $request->query('categoria_id') === null, function (Builder $query) {
                    $query->whereHas('bankAccount', function (Builder $query) {
                        $query->where('main_account', 1);
                    });
                })->whereHas('installments', function (Builder $query) use ($inicio, $fim, $request, $hoje) {
                    $query->whereBetween('due_date', [$inicio, $fim])
                        ->when($request->has('categoria_id'), function (Builder $query) use ($request) {
                            $query->where('cat_id', $request->query('categoria_id'));
                        })
                        ->when($request->query('status') === 'pago', function (Builder $query) {
                            $query->where('status', AccountsEnum::PAID->value);
                        })
                        ->when($request->query('status') === 'a-vencer', function (Builder $query) {
                            $query->where('status', AccountsEnum::OPEN->value);
                        })
                        ->when($request->query('status') === 'vencem-hoje', function (Builder $query) use ($hoje) {
                            $query->whereDate('data_de_vencimento', $hoje)
                                ->where('status', AccountsEnum::OPEN->value);
                        })
                        ->when($request->query('status') === 'vencidos', function (Builder $query) use ($hoje) {
                            $query->whereDate('data_de_vencimento', '<', $hoje)
                                ->where('status', AccountsEnum::OPEN->value);
                        });
                })->with([
                    'installment' => function ($query) use ($inicio, $fim) {
                        $query->whereBetween('data_de_vencimento', [$inicio, $fim]);
                    },
                ])->orderByDesc('id')
                ->paginate($request->query('quantidade', 10))
                ->appends($request->all());
        });
    }

    public function delete(string $id, Tenant $tenant)
    {
        return $tenant->run(fn () => $this->model::findOrFail($id)->delete());
    }

    public function updateInstallment(string $id, Tenant $tenant): bool
    {
        return $tenant->run(function () use ($id) {
            return DB::transaction(function () use ($id) {

                $installment = Installment::findOrFail($id);

                $model = $installment->installmentable_type;

                $bankAccountId = $model::select('bank_account_id')->where('id', $installment->installmentable_id)->first()->bank_account_id;

                $bankAccount = BankAccount::findOrFail($bankAccountId);

                if (class_basename($model) === 'AccountPayable') {
                    $bankAccount->current_balance -= $installment->value;
                    $bankAccount->save();
                }

                if (class_basename($model) === 'AccountReceivable') {
                    $bankAccount->current_balance += $installment->value;
                    $bankAccount->save();
                }

                $installment->payment_date = Carbon::now();

                $installment->status = AccountsEnum::PAID->value;

                return $installment->save();
            });
        });
    }

    public function paymentTerms()
    {
        return [
            '1' => '1x',
            '2' => '2x',
            '3' => '3x',
            '4' => '4x',
            '5' => '5x',
            '6' => '6x',
            '7' => '7x',
            '8' => '8x',
            '9' => '9x',
            '10' => '10x',
            '11' => '11x',
            '12' => '12x',
        ];
    }

    public function totalPeriod($request, string $period, Tenant $tenant, ?int $bankAccountId = null)
    {
        return $tenant->run(function () use ($request, $period, $bankAccountId) {
            $start = $request->query('inicio')
                ? Carbon::parse($request->query('inicio'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $period)->startOfMonth();

            $end = $request->query('fim')
                ? Carbon::parse($request->query('fim'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $period)->endOfMonth();

            $query = Installment::where(function (Builder $query) use ($request) {
                $search = $request->query('search');

                $query->when($search, function (Builder $query) use ($search) {
                    $query->where('description', 'like', "%{$search}%");
                });

                $query->when(is_numeric($search), function (Builder $query) use ($search) {
                    $value = (float) $search;
                    $margin = 100;
                    $query->orWhereBetween('value', [$value - $margin, $value + $margin]);
                });
            })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($start, $end, $request, $bankAccountId) {
                $query->whereBetween('due_date', [$start, $end])
                    ->when($request->query('categoria_id'), function (Builder $query) use ($request) {
                        $query->where('financial_category_id', $request->query('categoria_id'));
                    })
                    ->when($request->query('conta_id') !== null, function (Builder $query) use ($request) {
                        $query->where('bank_account_id', $request->query('conta_id'));
                    })
                    ->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
                        $query->where('bank_account_id', $bankAccountId);
                    });
            });

            return $query->sum('value');
        });
    }

    public function totalPaid($request, string $periodo, Tenant $tenant, ?int $bankAccountId = null)
    {
        return $tenant->run(function () use ($request, $periodo, $bankAccountId) {
            $start = $request->query('inicio')
                ? Carbon::parse($request->query('inicio'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();

            $end = $request->query('fim')
                ? Carbon::parse($request->query('fim'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $periodo)->endOfMonth();

            $query = Installment::where(function (Builder $query) use ($request) {
                $search = $request->query('search');

                $query->when($search, function (Builder $query) use ($search) {
                    $query->where('description', 'like', "%{$search}%");
                });

                $query->when(is_numeric($search), function (Builder $query) use ($search) {
                    $value = (float) $search;
                    $margin = 100;
                    $query->orWhereBetween('value', [$value - $margin, $value + $margin]);
                });
            })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($start, $end, $request, $bankAccountId) {
                $query->whereBetween('due_date', [$start, $end])
                    ->where('status', AccountsEnum::PAID->value)
                    ->when($request->query('categoria_id'), function (Builder $query) use ($request) {
                        $query->where('financial_category_id', $request->query('categoria_id'));
                    })
                    ->when($request->query('conta_id') !== null, function (Builder $query) use ($request) {
                        $query->where('bank_account_id', $request->query('conta_id'));
                    })
                    ->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
                        $query->where('bank_account_id', $bankAccountId);
                    })
                    ->when($request->query('search'), function (Builder $query) use ($request) {
                        $query->where(function ($query) use ($request) {
                            $search = $request->query('search');

                            $query->when($request->query('search'), function (Builder $query) use ($search) {
                                $query->where('description', 'like', "%{$search}%");
                            });

                            if (is_numeric($search)) {
                                $value = (float) $search;
                                $margin = 100;
                                $query->orWhereBetween('value', [$value - $margin, $value + $margin]);
                            }
                        })->where('status', AccountsEnum::PAID->value);
                    });
            });

            return $query->sum('value');
        });
    }

    public function totalToDue($request, int $days, string $period, Tenant $tenant, ?int $bankAccountId = null)
    {
        return $tenant->run(function () use ($request, $days, $period, $bankAccountId) {
            $statusOpen = AccountsEnum::OPEN->value;

            if ($request->query('inicio') && $request->query('fim')) {
                $intervalStart = Carbon::parse($request->query('inicio'))->startOfDay();
                $intervalEnd = Carbon::parse($request->query('fim'))->endOfDay();
                $today = now()->startOfDay();

                if ($today->lt($intervalStart) || $today->gt($intervalEnd)) {
                    return 0;
                }

                $start = $today;
                $end = $today->copy()->addDays($days);
            } else {
                $start = Carbon::today();
                $end = Carbon::today()->addDays($days);
                $monthYear = Carbon::createFromFormat('Y-m', $period);

                if ($monthYear->format('Y-m') !== now()->format('Y-m')) {
                    return 0;
                }
            }

            $query = Installment::where(function (Builder $query) use ($request) {
                $search = $request->query('search');

                $query->when($search, function (Builder $query) use ($search) {
                    $query->where('description', 'like', "%{$search}%");
                });

                $query->when(is_numeric($search), function (Builder $query) use ($search) {
                    $value = (float) $search;
                    $margin = 100;
                    $query->orWhereBetween('value', [$value - $margin, $value + $margin]);
                });
            })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($start, $end, $statusOpen, $request, $bankAccountId) {
                $query->where('status', $statusOpen)
                    ->whereBetween('due_date', [$start, $end])
                    ->when($request->filled('categoria_id'), function (Builder $query) use ($request) {
                        $query->where('financial_category_id', $request->query('categoria_id'));
                    })
                    ->when($request->query('conta_id') !== null, function (Builder $query) use ($request) {
                        $query->where('bank_account_id', $request->query('conta_id'));
                    })
                    ->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
                        $query->where('bank_account_id', $bankAccountId);
                    });
            });

            return $query->sum('value');
        });
    }

    public function totalDueToday($request, string $period, Tenant $tenant, ?int $bankAccountId = null)
    {
        return $tenant->run(function () use ($request, $period, $bankAccountId) {
            $statusOpen = AccountsEnum::OPEN->value;
            $today = now()->startOfDay();

            if ($request->query('inicio') && $request->query('fim')) {
                $intervalStart = Carbon::parse($request->query('inicio'))->startOfDay();
                $intervalEnd = Carbon::parse($request->query('fim'))->endOfDay();

                if ($today->lt($intervalStart) || $today->gt($intervalEnd)) {
                    return 0;
                }
            } else {
                $monthYear = Carbon::createFromFormat('Y-m', $period);
                if ($monthYear->format('Y-m') !== now()->format('Y-m')) {
                    return 0;
                }
            }

            $query = Installment::where(function (Builder $query) use ($request) {
                $search = $request->query('search');

                $query->when($search, function (Builder $query) use ($search) {
                    $query->where('description', 'like', "%{$search}%");
                });

                $query->when(is_numeric($search), function (Builder $query) use ($search) {
                    $value = (float) $search;
                    $margin = 100;
                    $query->orWhereBetween('value', [$value - $margin, $value + $margin]);
                });
            })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($today, $statusOpen, $request, $bankAccountId) {
                $query->whereDate('due_date', $today)
                    ->where('status', $statusOpen)
                    ->when($request->query('categoria_id'), function (Builder $query) use ($request) {
                        $query->where('financial_category_id', $request->query('categoria_id'));
                    })
                    ->when($request->query('conta_id') !== null, function (Builder $query) use ($request) {
                        $query->where('bank_account_id', $request->query('conta_id'));
                    })
                    ->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
                        $query->where('bank_account_id', $bankAccountId);
                    });
            });

            return $query->sum('value');
        });
    }

    public function totalOverdue($request, string $period, Tenant $tenant, ?int $bankAccountId = null)
    {
        return $tenant->run(function () use ($request, $period, $bankAccountId) {
            $statusOpen = AccountsEnum::OPEN->value;
            $today = Carbon::today();

            if ($request->query('inicio') && $request->query('fim')) {
                $start = Carbon::parse($request->query('inicio'))->startOfDay();
                $end = Carbon::parse($request->query('fim'))->endOfDay();
            } else {
                $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
                $end = Carbon::createFromFormat('Y-m', $period)->endOfMonth();
            }

            $query = Installment::where(function (Builder $query) use ($request) {
                $search = $request->query('search');

                $query->when($search, function (Builder $query) use ($search) {
                    $query->where('description', 'like', "%{$search}%");
                });

                $query->when(is_numeric($search), function (Builder $query) use ($search) {
                    $value = (float) $search;
                    $margin = 100;
                    $query->orWhereBetween('value', [$value - $margin, $value + $margin]);
                });
            })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($start, $end, $today, $statusOpen, $request, $bankAccountId) {
                $query->whereBetween('due_date', [$start, $end])
                    ->whereDate('due_date', '<', $today)
                    ->where('status', $statusOpen)
                    ->when($request->query('categoria_id'), function (Builder $query) use ($request) {
                        $query->where('financial_category_id', $request->query('categoria_id'));
                    })
                    ->when($request->query('conta_id') !== null, function (Builder $query) use ($request) {
                        $query->where('bank_account_id', $request->query('conta_id'));
                    })
                    ->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
                        $query->where('bank_account_id', $bankAccountId);
                    });
            });

            return $query->sum('value');
        });
    }

    public function updateInstallmentValue(array $data, Tenant $tenant)
    {
        return $tenant->run(function () use ($data) {
            $installment = Installment::findOrFail($data['id']);

            $installment->value = $data['value'];
            $installment->save();

            if ($installment) {
                return true;
            }

            return false;
        });
    }

    public function search($request, string $period, Tenant $tenant)
    {
        return $tenant->run(function () use ($request, $period) {
            $start = $request->query('inicio')
                ? Carbon::parse($request->query('inicio'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $period)->startOfMonth();

            $end = $request->query('fim ')
                ? Carbon::parse($request->query('fim'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $period)->endOfMonth();

            return Installment::with('installmentable')->where(function ($query) use ($request) {
                $search = $request->query('search');

                $query->when($search, function (Builder $query) use ($search) {
                    $query->where('description', 'like', "%{$search}%");
                });

                $query->when(is_numeric($search), function (Builder $query) use ($search) {
                    $value = (float) $search;
                    $margin = 100;
                    $query->orWhereBetween('value', [$value - $margin, $value + $margin]);
                });
            })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($start, $end) {
                $query->whereBetween('due_date', [$start, $end]);
            })->paginate($request->query('quantidade', 10))
                ->appends([
                    'periodo' => $request->query('periodo'),
                    'quantidade' => $request->query('quantidade'),
                    'inicio' => $request->query('inicio'),
                    'fim' => $request->query('fim'),
                    'categoria_id' => $request->query('categoria_id'),
                    'tipo_conta' => $request->query('tipo_conta'),
                    'search' => $request->query('search'),
                ]);
        });
    }
}
