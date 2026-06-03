<?php

namespace App\Services;

use App\Enums\AccountsEnum;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\BankAccount;
use App\Models\Installment;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class BillingFlowService
{
    /**
     * Calcula o faturamento por conta bancária e período
     */
    public function calculateBilling(int $startYear, int $endYear, Tenant $tenant, ?int $bankAccountId = null): array
    {
        return $tenant->run(function () use ($bankAccountId, $startYear, $endYear) {
            $query = BankAccount::query()
                ->where('active', true)
                ->with(['accountsPayable.installments', 'accountsReceivable.installments']);

            if ($bankAccountId) {
                $query->where('id', $bankAccountId);
            }

            $bankAccounts = $query->get();

            $result = [
                'data_by_account' => [],
                'monthly_comparison' => $this->calculateComparativeMonthly($startYear, $endYear, $bankAccountId),
                'general_summary' => [],
            ];

            foreach ($bankAccounts as $bankAccount) {
                $data = $this->processBankAccount($bankAccount, $startYear, $endYear);
                $result['data_by_account'][$bankAccount->id] = [
                    'account' => $bankAccount,
                    'invoicing' => $data,
                ];
            }

            $result['general_summary'] = $this->calculateGeneralSummary($result['data_by_account']);

            return $result;
        });
    }

    /**
     * Processa os dados de faturamento de uma conta bancária específica
     */
    private function processBankAccount(BankAccount $bankAccount, int $startYear, int $endYear): array
    {
        $billingByYear = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $billingByYear[$year] = [
                'months' => $this->calculateMonthlyBilling($bankAccount, $year),
                'annual_value' => 0,
                'percentage_variation' => 0,
            ];

            $billingByYear[$year]['annual_total'] = array_sum($billingByYear[$year]['months']);

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
     * Calcula o faturamento mensal (contas a receber - contas a pagar)
     */
    private function calculateMonthlyBilling(BankAccount $bankAccount, int $year): array
    {
        $monthlyBilling = array_fill(1, 12, 0);

        // Busca parcelas pagas de contas a receber
        $receivableInstallments = Installment::whereHasMorph('installmentable', [AccountReceivable::class], function ($query) use ($bankAccount) {
            $query->where('bank_account_id', $bankAccount->id);
        })
            ->where('status', AccountsEnum::PAID->value)
            ->whereYear('payment_date', $year)
            ->select(
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(value) as total')
            )
            ->groupBy('month')
            ->get();

        foreach ($receivableInstallments as $installment) {
            $monthlyBilling[$installment->month] += $installment->total;
        }

        // Busca parcelas pagas de contas a pagar (subtrai do faturamento)
        $payableInstallments = Installment::whereHasMorph('installmentable', [AccountPayable::class], function ($query) use ($bankAccount) {
            $query->where('bank_account_id', $bankAccount->id);
        })
            ->where('status', AccountsEnum::PAID->value)
            ->whereYear('payment_date', $year)
            ->select(
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(value) as total')
            )
            ->groupBy('month')
            ->get();

        foreach ($payableInstallments as $installment) {
            $monthlyBilling[$installment->month] -= $installment->total;
        }

        return $monthlyBilling;
    }

    /**
     * Calcula a comparação mensal consolidada de todas as contas
     */
    private function calculateComparativeMonthly(int $startYear, int $endYear, ?int $bankAccountId = null): array
    {
        $comparison = [];
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

        foreach ($months as $monthNumber => $monthName) {
            $totalsByYear = [];

            for ($year = $startYear; $year <= $endYear; $year++) {
                $totalsByYear[$year] = $this->calculateTotalMonthYear($monthNumber, $year, $bankAccountId);
            }

            $comparison[$monthName] = [
                'totals_by_year' => $totalsByYear,
                'total_period' => array_sum($totalsByYear),
            ];
        }

        return $comparison;
    }

    /**
     * Calcula o total de um mês específico em um ano
     */
    private function calculateTotalMonthYear(int $month, int $year, ?int $bankAccountId = null): float
    {
        $receivableQuery = Installment::whereHasMorph('installmentable', [AccountReceivable::class], function ($query) use ($bankAccountId) {
            if ($bankAccountId) {
                $query->where('bank_account_id', $bankAccountId);
            }
        })
            ->where('status', AccountsEnum::PAID->value)
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->sum('value');

        $payableQuery = Installment::whereHasMorph('installmentable', [AccountPayable::class], function ($query) use ($bankAccountId) {
            if ($bankAccountId) {
                $query->where('bank_account_id', $bankAccountId);
            }
        })
            ->where('status', AccountsEnum::PAID->value)
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->sum('value');

        return $receivableQuery - $payableQuery;
    }

    /**
     * Calcula resumo geral consolidado
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
     * Exporta dados para formato adequado para gráficos
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
