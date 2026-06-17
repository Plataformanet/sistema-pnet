<?php

namespace App\Services;

use App\Enums\AccountsEnum;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\BankAccount;
use App\Models\Installment;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class BillingFlowService
{
    /**
     * Calcula o faturamento por conta bancária e período.
     *
     * @return array{
     *     data_by_account: array<int, array{account: BankAccount, invoicing: array<int, array<string, mixed>>}>,
     *     monthly_comparison: array<string, array{totals_by_year: array<int, int>, total_period: int}>,
     *     general_summary: array<string, mixed>
     * }
     */
    public function calculateBilling(int $startYear, int $endYear, Tenant $tenant, ?int $bankAccountId = null): array
    {
        return $tenant->run(function () use ($bankAccountId, $startYear, $endYear) {
            $query = BankAccount::query()->where('active', true);

            if ($bankAccountId) {
                $query->where('id', $bankAccountId);
            }

            $bankAccounts = $query->get();

            // Uma query agregada por tipo (receber/pagar) para todo o período,
            // agrupada por conta, ano e mês. O resto é montado em memória.
            $net = $this->buildNetMatrix($startYear, $endYear, $bankAccountId);

            $result = [
                'data_by_account' => [],
                'monthly_comparison' => $this->calculateComparativeMonthly($net, $startYear, $endYear),
                'general_summary' => [],
            ];

            foreach ($bankAccounts as $bankAccount) {
                $result['data_by_account'][$bankAccount->id] = [
                    'account' => $bankAccount,
                    'invoicing' => $this->processBankAccount($net[$bankAccount->id] ?? [], $startYear, $endYear),
                ];
            }

            $result['general_summary'] = $this->calculateGeneralSummary($result['data_by_account']);

            return $result;
        });
    }

    /**
     * Monta a matriz líquida (receber - pagar) por conta/ano/mês a partir de
     * duas queries agregadas, evitando o loop de consultas por mês e por ano.
     *
     * @return array<int, array<int, array<int, int>>> [bankAccountId][year][month] => valor
     */
    private function buildNetMatrix(int $startYear, int $endYear, ?int $bankAccountId): array
    {
        $net = [];

        foreach ($this->aggregatePaidInstallments(AccountReceivable::class, 'account_receivables', $startYear, $endYear, $bankAccountId) as $row) {
            $net[$row->bank_account_id][(int) $row->year][(int) $row->month]
                = ($net[$row->bank_account_id][(int) $row->year][(int) $row->month] ?? 0) + (int) $row->total;
        }

        foreach ($this->aggregatePaidInstallments(AccountPayable::class, 'account_payables', $startYear, $endYear, $bankAccountId) as $row) {
            $net[$row->bank_account_id][(int) $row->year][(int) $row->month]
                = ($net[$row->bank_account_id][(int) $row->year][(int) $row->month] ?? 0) - (int) $row->total;
        }

        return $net;
    }

    /**
     * Soma as parcelas pagas de um tipo de conta, agrupadas por conta bancária,
     * ano e mês, numa única query. Usa range em `payment_date` (em vez de
     * whereYear/whereMonth) para aproveitar o índice da coluna.
     *
     * @param  class-string  $morphClass
     * @return Collection<int, \stdClass>
     */
    private function aggregatePaidInstallments(string $morphClass, string $table, int $startYear, int $endYear, ?int $bankAccountId): Collection
    {
        return Installment::query()
            ->join($table, function ($join) use ($table, $morphClass) {
                $join->on('installments.installmentable_id', '=', "{$table}.id")
                    ->where('installments.installmentable_type', '=', $morphClass);
            })
            ->whereNull("{$table}.deleted_at")
            ->where('installments.status', AccountsEnum::PAID->value)
            ->whereBetween('installments.payment_date', ["{$startYear}-01-01", "{$endYear}-12-31"])
            ->when($bankAccountId, fn ($query) => $query->where("{$table}.bank_account_id", $bankAccountId))
            ->groupBy('bank_account_id', 'year', 'month')
            ->selectRaw("{$table}.bank_account_id as bank_account_id, YEAR(installments.payment_date) as year, MONTH(installments.payment_date) as month, SUM(installments.value) as total")
            ->get();
    }

    /**
     * Processa os dados de faturamento de uma conta bancária específica.
     *
     * @param  array<int, array<int, int>>  $accountNet  [year][month] => valor
     * @return array<int, array{months: array<int, int>, annual_total: int, percentage_variation: float}>
     */
    private function processBankAccount(array $accountNet, int $startYear, int $endYear): array
    {
        $billingByYear = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $months = array_fill(1, 12, 0);

            foreach ($accountNet[$year] ?? [] as $month => $value) {
                $months[$month] = $value;
            }

            $billingByYear[$year] = [
                'months' => $months,
                'annual_total' => array_sum($months),
                'percentage_variation' => 0,
            ];

            // Calcula variação percentual em relação ao ano anterior
            if (isset($billingByYear[$year - 1])) {
                $previousValue = $billingByYear[$year - 1]['annual_total'];
                if ($previousValue > 0) {
                    $billingByYear[$year]['percentage_variation'] =
                        (($billingByYear[$year]['annual_total'] - $previousValue) / $previousValue) * 100;
                }
            }
        }

        return $billingByYear;
    }

    /**
     * Calcula a comparação mensal consolidada de todas as contas.
     *
     * @param  array<int, array<int, array<int, int>>>  $net  [bankAccountId][year][month] => valor
     * @return array<string, array{totals_by_year: array<int, int>, total_period: int}>
     */
    private function calculateComparativeMonthly(array $net, int $startYear, int $endYear): array
    {
        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        // Consolida todas as contas em [year][month] => valor
        $consolidated = [];
        foreach ($net as $accountNet) {
            foreach ($accountNet as $year => $monthValues) {
                foreach ($monthValues as $month => $value) {
                    $consolidated[$year][$month] = ($consolidated[$year][$month] ?? 0) + $value;
                }
            }
        }

        $comparison = [];
        foreach ($months as $monthNumber => $monthName) {
            $totalsByYear = [];

            for ($year = $startYear; $year <= $endYear; $year++) {
                $totalsByYear[$year] = $consolidated[$year][$monthNumber] ?? 0;
            }

            $comparison[$monthName] = [
                'totals_by_year' => $totalsByYear,
                'total_period' => array_sum($totalsByYear),
            ];
        }

        return $comparison;
    }

    /**
     * Calcula resumo geral consolidado.
     *
     * @param  array<int, array{account: BankAccount, invoicing: array<int, array<string, mixed>>}>  $dataByAccount
     * @return array<string, mixed>
     */
    private function calculateGeneralSummary(array $dataByAccount): array
    {
        $summary = [
            'total_active_accounts' => count($dataByAccount),
            'total_period_billing' => 0,
            'best_year' => null,
            'worst_year' => null,
            'monthly_average' => 0,
        ];

        $totalsByYear = [];

        foreach ($dataByAccount as $data) {
            foreach ($data['invoicing'] as $year => $yearData) {
                if (! isset($totalsByYear[$year])) {
                    $totalsByYear[$year] = 0;
                }
                $totalsByYear[$year] += $yearData['annual_total'];
            }
        }

        if (! empty($totalsByYear)) {
            $summary['total_period_billing'] = array_sum($totalsByYear);
            $summary['best_year'] = [
                'year' => array_keys($totalsByYear, max($totalsByYear))[0],
                'value' => max($totalsByYear),
            ];
            $summary['worst_year'] = [
                'year' => array_keys($totalsByYear, min($totalsByYear))[0],
                'value' => min($totalsByYear),
            ];
            $summary['monthly_average'] = $summary['total_period_billing'] / (count($totalsByYear) * 12);
        }

        return $summary;
    }

    /**
     * Exporta dados para formato adequado para gráficos.
     *
     * @param  array{monthly_comparison: array<string, array{totals_by_year: array<int, int>, total_period: int}>}  $data
     * @return array{labels: array<int, string>, datasets: array<int, array{label: int, data: array<int, float>}>}
     */
    public function formatForChart(array $data): array
    {
        $labels = [];
        $datasets = [];

        foreach ($data['monthly_comparison'] as $month => $monthData) {
            $labels[] = $month;

            foreach ($monthData['totals_by_year'] as $year => $value) {
                if (! isset($datasets[$year])) {
                    $datasets[$year] = [
                        'label' => $year,
                        'data' => [],
                    ];
                }
                $datasets[$year]['data'][] = round($value, 2);
            }
        }

        return [
            'labels' => $labels,
            'datasets' => array_values($datasets),
        ];
    }
}
