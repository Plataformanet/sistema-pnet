<?php

namespace App\Services;

use App\Enums\AccountsEnum;
use App\Enums\TypeContactEnum;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\FinancialContact;
use App\Models\Installment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class AccountService
{
    protected string $model; // Ex: AccountPayable::class ou AccountReceivable::class

    public function __construct()
    {
        $this->model = $this->getModel();
    }

    abstract protected function getModel(): string;

    /**
     * Gera as parcelas no servidor a partir de `total_installments`, deslocando o
     * vencimento mês a mês. Usado quando o front não envia parcelas (à vista e 1 parcela).
     *
     * @param  array<string, mixed>  $data
     */
    protected function generateInstallments(Model $account, array $data, int|float $installmentValue): void
    {
        $dueDate = Carbon::parse($data['due_date']);

        for ($count = 0; $count < $data['total_installments']; $count++) {
            $account->installments()->create([
                'installment_number' => $count + 1,
                'value' => $installmentValue,
                'description' => $data['description'],
                'due_date' => $dueDate->copy()->addMonthsNoOverflow($count),
                'payment_date' => $dueDate->copy()->addMonthsNoOverflow($count),
                'status' => $data['status'] ?? AccountsEnum::OPEN->value,
            ]);
        }
    }

    /**
     * Resolve (ou cria) o financial_contact correspondente ao contato selecionado
     * no formulário e devolve o id da tabela financial_contacts para o FK.
     */
    protected function resolveFinancialContactId(int $contactId, TypeContactEnum $type): int
    {
        return FinancialContact::firstOrCreate([
            'contact_id' => $contactId,
            'type' => $type->value,
        ])->id;
    }

    public function findAll($request, string $periodo, Tenant $tenant)
    {
        return $tenant->run(function () use ($request, $periodo) {
            $inicio = $request->query('inicio')
                ? Carbon::parse($request->query('inicio'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();

            $fim = $request->query('fim')
                ? Carbon::parse($request->query('fim'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $periodo)->endOfMonth();

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
                })
                ->when($request->query('search'), function (Builder $query) use ($request) {
                    $search = $request->query('search');
                    $query->where(function (Builder $query) use ($search) {
                        $query->where('description', 'like', "%{$search}%")
                            ->orWhereHas('financialContact.contact', function (Builder $query) use ($search) {
                                $query->where('name_corporatereason', 'like', "%{$search}%")
                                    ->orWhere('fantasy_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('installments', function (Builder $query) use ($search) {
                                $query->where('description', 'like', "%{$search}%");
                            });

                        if (is_numeric($search)) {
                            $value = (float) $search;
                            $margin = 100;
                            $query->orWhereBetween('total', [$value - $margin, $value + $margin])
                                ->orWhereHas('installments', function (Builder $query) use ($value, $margin) {
                                    $query->whereBetween('value', [$value - $margin, $value + $margin]);
                                });
                        }
                    });
                })
                ->whereHas('installments', function (Builder $query) use ($inicio, $fim, $request, $hoje) {
                    $query->whereBetween('due_date', [$inicio, $fim])
                        ->when($request->has('categoria_id'), function (Builder $query) use ($request) {
                            $query->where('financial_category_id', $request->query('categoria_id'));
                        })
                        ->when($request->query('status') === 'pago', function (Builder $query) {
                            $query->where('status', AccountsEnum::PAID->value);
                        })
                        ->when($request->query('status') === 'a-vencer', function (Builder $query) {
                            $query->where('status', AccountsEnum::OPEN->value)
                                ->whereDate('due_date', '>=', Carbon::today());
                        })
                        ->when($request->query('status') === 'vencem-hoje', function (Builder $query) use ($hoje) {
                            $query->whereDate('due_date', $hoje)
                                ->where('status', AccountsEnum::OPEN->value);
                        })
                        ->when($request->query('status') === 'vencidos', function (Builder $query) use ($hoje) {
                            $query->whereDate('due_date', '<', $hoje)
                                ->where('status', AccountsEnum::OPEN->value);
                        });
                })->with([
                    'bankAccount',
                    'financialCategory',
                    'financialSubcategory',
                    'installments' => function ($query) use ($inicio, $fim, $request, $hoje) {
                        $query->whereBetween('due_date', [$inicio, $fim])
                            ->when($request->query('status') === 'pago', function (Builder $query) {
                                $query->where('status', AccountsEnum::PAID->value);
                            })
                            ->when($request->query('status') === 'a-vencer', function (Builder $query) {
                                $query->where('status', AccountsEnum::OPEN->value)
                                    ->whereDate('due_date', '>=', Carbon::today());
                            })
                            ->when($request->query('status') === 'vencem-hoje', function (Builder $query) use ($hoje) {
                                $query->whereDate('due_date', $hoje)
                                    ->where('status', AccountsEnum::OPEN->value);
                            })
                            ->when($request->query('status') === 'vencidos', function (Builder $query) use ($hoje) {
                                $query->whereDate('due_date', '<', $hoje)
                                    ->where('status', AccountsEnum::OPEN->value);
                            });
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

    public function paymentConditions()
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

            $query = Installment::whereBetween('due_date', [$start, $end])
                ->where(function (Builder $query) use ($request) {
                    $this->applySearchFilter($query, $request->query('search'));
                })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($request, $bankAccountId) {
                    $query->when($request->query('categoria_id'), function (Builder $query) use ($request) {
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

            $query = Installment::where('status', AccountsEnum::PAID->value)
                ->whereBetween('due_date', [$start, $end])
                ->where(function (Builder $query) use ($request) {
                    $this->applySearchFilter($query, $request->query('search'));
                })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($request, $bankAccountId) {
                    $query->when($request->query('categoria_id'), function (Builder $query) use ($request) {
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

    public function totalToDue($request, int $days, string $period, Tenant $tenant, ?int $bankAccountId = null)
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

            // We only want outstanding items, so due_date must be >= today
            $start = $today->max($start);

            // If start is after end, return 0
            if ($start->gt($end)) {
                return 0;
            }

            $query = Installment::where('status', $statusOpen)
                ->whereBetween('due_date', [$start, $end])
                ->where(function (Builder $query) use ($request) {
                    $this->applySearchFilter($query, $request->query('search'));
                })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($request, $bankAccountId) {
                    $query->when($request->filled('categoria_id'), function (Builder $query) use ($request) {
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

            $query = Installment::where('status', $statusOpen)
                ->whereDate('due_date', $today)
                ->where(function (Builder $query) use ($request) {
                    $this->applySearchFilter($query, $request->query('search'));
                })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($request, $bankAccountId) {
                    $query->when($request->query('categoria_id'), function (Builder $query) use ($request) {
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

            $query = Installment::where('status', $statusOpen)
                ->whereBetween('due_date', [$start, $end])
                ->whereDate('due_date', '<', $today)
                ->where(function (Builder $query) use ($request) {
                    $this->applySearchFilter($query, $request->query('search'));
                })->whereHasMorph('installmentable', [$this->model], function (Builder $query) use ($request, $bankAccountId) {
                    $query->when($request->query('categoria_id'), function (Builder $query) use ($request) {
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
                $this->applySearchFilter($query, $request->query('search'));
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

    public function searchContact($request)
    {
        $search = $request->query('search');
        $type = $request->query('type');

        $query = Contact::select('id', 'name_corporatereason');

        if ($type === 'client') {
            $query->whereHas('client');
        } elseif ($type === 'supplier') {
            $query->whereHas('supplier');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name_corporatereason', 'like', "%{$search}%")
                    ->orWhere('fantasy_name', 'like', "%{$search}%")
                    ->orWhere('cpf_cnpj', 'like', "%{$search}%");
            });
        }

        $contacts = $query->limit(15)->get();

        return $contacts;
    }

    protected function applySearchFilter(Builder $query, ?string $search)
    {
        if (! $search) {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
                ->orWhereHasMorph('installmentable', [$this->model], function (Builder $q) use ($search) {
                    $q->whereHas('financialContact.contact', function (Builder $q) use ($search) {
                        $q->where('name_corporatereason', 'like', "%{$search}%")
                            ->orWhere('fantasy_name', 'like', "%{$search}%");
                    });
                });

            if (is_numeric($search)) {
                $value = (float) $search;
                $margin = 100;
                $q->orWhereBetween('value', [$value - $margin, $value + $margin]);
            }
        });
    }
}
