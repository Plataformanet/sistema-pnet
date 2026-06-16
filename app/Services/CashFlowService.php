<?php

namespace App\Services;

use App\Enums\AccountsEnum;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Installment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CashFlowService
{
    /**
     * Resolve the period boundaries from the request, falling back to the given month.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(Request $request, string $period): array
    {
        $start = $request->query('start')
            ? Carbon::parse($request->query('start'))->startOfDay()
            : Carbon::createFromFormat('Y-m', $period)->startOfMonth();

        $end = $request->query('end')
            ? Carbon::parse($request->query('end'))->endOfDay()
            : Carbon::createFromFormat('Y-m', $period)->endOfMonth();

        return [$start, $end];
    }

    public function findAll(Request $request, string $period, Tenant $tenant, ?int $bankAccountId = null): LengthAwarePaginator
    {
        return $tenant->run(function () use ($request, $period, $bankAccountId) {
            [$start, $end] = $this->resolvePeriod($request, $period);

            // Query ContaAPagar
            $payableAccounts = $this->getAccounts(AccountPayable::class, $start, $end, $request, $bankAccountId);

            // Query ContaAReceber
            $receivableAccounts = $this->getAccounts(AccountReceivable::class, $start, $end, $request, $bankAccountId);

            $accounts = collect();

            if ($request->query('status') === 'expenses') {
                $accounts = $payableAccounts;
            }

            if ($request->query('status') === 'revenues') {
                $accounts = $receivableAccounts;
            }

            // Junta tudo em uma Collection
            if (! $request->has('status')) {
                $accounts = $payableAccounts->concat($receivableAccounts);
            }

            $perPage = $request->query('quantity', 10);
            $page = $request->query('page', 1);
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

    private function getAccounts(string $model, Carbon $start, Carbon $end, Request $request, ?int $bankAccountId): Collection
    {
        $statuses = [AccountsEnum::PAID, AccountsEnum::OPEN, AccountsEnum::OVERDUE];

        return $model::with([
            'bankAccount',
            'financialCategory',
            'installments' => function (MorphMany $query) use ($start, $end, $statuses) {
                $query->whereBetween('due_date', [$start, $end])
                    ->whereIn('status', $statuses);
            },
        ])->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        })->when($request->query('category_id') !== null, function (Builder $query) use ($request) {
            $query->where('financial_category_id', $request->query('category_id'));
        })->whereHas('installments', function (Builder $query) use ($start, $end, $statuses) {
            $query->whereBetween('due_date', [$start, $end])
                ->whereIn('status', $statuses);
        })->get();
    }

    public function expenses(Request $request, string $period, Tenant $tenant, ?int $bankAccountId = null): int
    {
        return $tenant->run(function () use ($request, $period, $bankAccountId) {
            [$start, $end] = $this->resolvePeriod($request, $period);

            $query = Installment::query()
                ->whereBetween('due_date', [$start, $end])
                ->where('status', AccountsEnum::PAID)
                ->whereHasMorph('installmentable', [AccountPayable::class], function (Builder $query) use ($request, $bankAccountId) {
                    $query->when($request->query('category_id'), function (Builder $query) use ($request) {
                        $query->where('financial_category_id', $request->query('category_id'));
                    })->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
                        $query->where('bank_account_id', $bankAccountId);
                    });
                });

            return (int) $query->sum('value');
        });
    }

    public function revenues(Request $request, string $period, Tenant $tenant, ?int $bankAccountId = null): int
    {
        return $tenant->run(function () use ($request, $period, $bankAccountId) {
            [$start, $end] = $this->resolvePeriod($request, $period);

            $query = Installment::query()
                ->whereBetween('due_date', [$start, $end])
                ->where('status', AccountsEnum::PAID)
                ->whereHasMorph('installmentable', [AccountReceivable::class], function (Builder $query) use ($request, $bankAccountId) {
                    $query->when($request->query('category_id'), function (Builder $query) use ($request) {
                        $query->where('financial_category_id', $request->query('category_id'));
                    })->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
                        $query->where('bank_account_id', $bankAccountId);
                    });
                });

            return (int) $query->sum('value');
        });
    }

    public function calculateAccounts(Request $request, string $period, Tenant $tenant, ?int $bankAccountId = null): Collection
    {
        return $tenant->run(function () use ($request, $period, $bankAccountId) {
            [$start, $end] = $this->resolvePeriod($request, $period);

            $query = Installment::query()
                ->with('installmentable:id,bank_account_id')
                ->whereBetween('due_date', [$start, $end])
                ->whereIn('status', [AccountsEnum::OPEN, AccountsEnum::OVERDUE])
                ->whereHasMorph('installmentable', [AccountPayable::class, AccountReceivable::class], function (Builder $query) use ($bankAccountId) {
                    $query->when($bankAccountId !== null, function (Builder $query) use ($bankAccountId) {
                        $query->where('bank_account_id', $bankAccountId);
                    });
                });

            return $query->get();
        });
    }
}
