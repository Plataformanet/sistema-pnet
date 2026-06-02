<?php

namespace App\Services;

use App\Enums\AccountsEnum;
use App\Models\AccountPayable;

use App\Models\AccountReceivable;
use App\Models\Installment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use \Illuminate\Pagination\LengthAwarePaginator;

class CashFlowService
{
    public function findAll($request, string $period, Tenant $tenant)
    {
        return $tenant->run(function () use ($request, $period) {
            $start = $request->query('start')
                ? Carbon::parse($request->query('start'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $period)->startOfMonth();

            $end = $request->query('end')
                ? Carbon::parse($request->query('end'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $period)->endOfMonth();

            // Query ContaAPagar
            $payableAccounts = $this->getAccounts(AccountPayable::class, $start, $end, $request);

            // Query ContaAReceber
            $receivableAccounts = $this->getAccounts(AccountReceivable::class, $start, $end, $request);

            $accounts = collect();

            if ($request->query('status') === 'expenses') {
                $accounts = $payableAccounts;
            }

            if ($request->query('status') === 'revenues') {
                $accounts = $receivableAccounts;
            }

            // Junta tudo em uma Collection
            if (!$request->has('status')) {
                $accounts = $payableAccounts->concat($receivableAccounts);
            }

            $perPage   = $request->query('quantity', 10);
            $page      = $request->query('page', 1);
            $paginated = new LengthAwarePaginator(
                $accounts->forPage($page, $perPage),
                $accounts->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            return $paginated->appends($request->all());
        });
    }

    static public function getAccounts(string $model, string $start, string $end, $request)
    {
        return $model::with([
            'installments' => function (MorphMany $query) use ($start, $end) {
                $query->whereBetween('due_date', [$start, $end])
                    ->whereIn('status', [AccountsEnum::PAID, AccountsEnum::OPEN]);
            }
        ])->when($request->query('account_id') !== null, function (Builder $query) use ($request) {
            $query->whereHas('accountBank', function (Builder $query) use ($request) {
                $query->where('account_bank_id', $request->query('account_id'));
            });
        })->when($request->query('category_id') !== null, function (Builder $query) use ($request) {
            $query->whereHas('financialCategory', function (Builder $query) use ($request) {
                $query->where('category_financial_id', $request->query('category_id'));
            });
        })->when($request->query('account_id') === null && $request->query('category_id') === null, function (Builder $query) {
            $query->whereHas('accountBank', function (Builder $query) {
                $query->where('main_account', 1);
            });
        })->whereHas('installments', function (Builder $query) use ($start, $end) {
            $query->whereBetween('due_date', [$start, $end])
                ->whereIn('status', [AccountsEnum::PAID, AccountsEnum::OPEN]);
        })->get();
    }

    public function totalPeriod($request, string $period, Tenant $tenant, ?int $accountBankId = null)
    {
        return $tenant->run(function () use ($request, $period, $accountBankId) {
            $start = $request->query('start')
                ? Carbon::parse($request->query('start'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $period)->startOfMonth();

            $end = $request->query('end')
                ? Carbon::parse($request->query('end'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $period)->endOfMonth();

            $query = Installment::whereBetween('due_date', [$start, $end])
                // ->where('status', ContasEnum::PAGO)
                ->whereHasMorph('installmentable', [AccountPayable::class, AccountReceivable::class], function (Builder $query) use ($request, $accountBankId) {
                    $query->when($request->query('account_id') !== null, function (Builder $query) use ($request) {
                        $query->where('account_bank_id', $request->query('account_id'));
                    })->when($accountBankId !== null, function (Builder $query) use ($accountBankId) {
                        $query->where('account_bank_id', $accountBankId);
                    });
                });

            return $query->sum('value');
        });
    }

    public function expenses($request, string $period, Tenant $tenant, ?int $accountBankId = null)
    {
        return $tenant->run(function () use ($request, $period, $accountBankId) {
            $start = $request->query('start')
                ? Carbon::parse($request->query('start'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $period)->startOfMonth();

            $end = $request->query('end')
                ? Carbon::parse($request->query('end'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $period)->endOfMonth();

            $query = Installment::whereHasMorph('installmentable', [AccountPayable::class], function (Builder $query) use ($start, $end, $request, $accountBankId) {
                $query->whereBetween('due_date', [$start, $end])
                    ->where('status', AccountsEnum::PAID)
                    ->when($request->query('category_id'), function (Builder $query) use ($request) {
                        $query->where('category_financial_id', $request->query('category_id'));
                    })->when($request->query('account_id') !== null, function (Builder $query) use ($request) {
                        $query->where('account_bank_id', $request->query('account_id'));
                    })->when($accountBankId !== null, function (Builder $query) use ($accountBankId) {
                        $query->where('account_bank_id', $accountBankId);
                    });
            });

            return $query->sum('value');
        });
    }

    public function revenues($request, string $period, Tenant $tenant, ?int $accountBankId = null)
    {
        return $tenant->run(function () use ($request, $period, $accountBankId) {
            $start = $request->query('start')
                ? Carbon::parse($request->query('start'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $period)->startOfMonth();

            $end = $request->query('end')
                ? Carbon::parse($request->query('end'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $period)->endOfMonth();

            $query = Installment::whereHasMorph('installmentable', [AccountReceivable::class], function (Builder $query) use ($start, $end, $request, $accountBankId) {
                $query->whereBetween('due_date', [$start, $end])
                    ->where('status', AccountsEnum::PAID)
                    ->when($request->query('category_id'), function (Builder $query) use ($request) {
                        $query->where('category_financial_id', $request->query('category_id'));
                    })->when($request->query('account_id') !== null, function (Builder $query) use ($request) {
                        $query->where('account_bank_id', $request->query('account_id'));
                    })->when($accountBankId !== null, function (Builder $query) use ($accountBankId) {
                        $query->where('account_bank_id', $accountBankId);
                    });
            });

            return $query->sum('value');
        });
    }


    public function calculateAccounts($request, string $period, Tenant $tenant, ?int $accountBankId = null)
    {
        return $tenant->run(function () use ($request, $period, $accountBankId) {
            $start = $request->query('start')
                ? Carbon::parse($request->query('start'))->startOfDay()
                : Carbon::createFromFormat('Y-m', $period)->startOfMonth();

            $end = $request->query('end')
                ? Carbon::parse($request->query('end'))->endOfDay()
                : Carbon::createFromFormat('Y-m', $period)->endOfMonth();

            $query = Installment::whereHasMorph('installmentable', [AccountPayable::class, AccountReceivable::class], function (Builder $query) use ($start, $end, $accountBankId) {
                $query->whereBetween('due_date', [$start, $end])
                    ->where('status', AccountsEnum::OPEN)
                    ->where('account_bank_id', $accountBankId);
            });

            return $query->get();
        });
    }
}
