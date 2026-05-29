<?php

namespace App\Services;

use App\Enums\AccountsEnum;
use App\Models\AccountBank;
use App\Models\Installment;
use Illuminate\Support\Facades\DB;

class BillingFlowService
{
    /**
     * Calcula o faturamento por conta bancária e período
     *
     * @param int|null $accountBankId
     * @param int $startYear
     * @param int $endYear
     * @return array
     */

    public function calculateBilling(?int $accountBankId = null, int $startYear, int $endYear): array
    {
        $query = AccountBank::query()
            ->where('active', true)
            ->with(['accountPayables.installments', 'accountReceivables.installments']);

        if ($accountBankId) {
            $query->where('id', $accountBankId);
        }

        $accountBanks = $query->get();

        $result = [
            'dados_por_conta'   => [],
            'comparacao_mensal' => $this->calculateComparativeMonthly($startYear, $endYear, $accountBankId),
            'resumo_geral'      => []
        ];

        foreach ($accountBanks as $accountBank) {
            $data                                        = $this->processAccountBank($accountBank, $startYear, $endYear);
            $result['dados_por_conta'][$accountBank->id] = [
                'conta'       => $accountBank,
                'faturamento' => $data
            ];
        }

        $result['resumo_geral'] = $this->calculateGeneralSummary($result['dados_por_conta']);

        return $result;
    }

    /**
     * Processa os dados de faturamento de uma conta bancária específica
     *
     * @param AccountBank $accountBank
     * @param int $startYear
     * @param int $endYear
     * @return array
     */
    private function processAccountBank(AccountBank $accountBank, int $startYear, int $endYear): array
    {
        $billingByYear = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $billingByYear[$year] = [
                'meses'               => $this->calculateMonthlyBilling($accountBank, $year),
                'valor_anual'         => 0,
                'variacao_percentual' => 0
            ];

            $billingByYear[$year]['total_anual'] = array_sum($billingByYear[$year]['meses']);

            // Calcula variação percentual em relação ao ano anterior
            if (isset($billingByYear[$year - 1])) {
                $valorAnterior = $billingByYear[$year - 1]['total_anual'];
                if ($valorAnterior > 0) {
                    $billingByYear[$year]['variacao_percentual'] =
                        (($billingByYear[$year]['total_anual'] - $valorAnterior) / $valorAnterior) * 100;
                }
            }
        }

        return $billingByYear;
    }

    /**
     * Calcula o faturamento mensal (contas a receber - contas a pagar)
     *
     * @param AccountBank $accountBank
     * @param int $year
     * @return array
     */
    private function calculateMonthlyBilling(AccountBank $accountBank, int $year): array
    {
        $faturamentoMeses = array_fill(1, 12, 0);

        // Busca parcelas pagas de contas a receber
        $parcelasReceber = Installment::whereHasMorph('parcelable', ['App\Models\AccountsReceivable'], function ($query) use ($accountBank) {
            $query->where('contact_financial_id', $accountBank->id);
        })
            ->where('status', AccountsEnum::PAID->value)
            ->whereYear('payment_date', $year)
            ->select(
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(value) as total')
            )
            ->groupBy('month')
            ->get();

        foreach ($parcelasReceber as $installment) {
            $faturamentoMeses[$installment->month] += $installment->total;
        }

        // Busca parcelas pagas de contas a pagar (subtrai do faturamento)
        $parcelasPagar = Installment::whereHasMorph('parcelable', ['App\Models\AccountPayable'], function ($query) use ($accountBank) {
            $query->where('contact_financial_id', $accountBank->id);
        })
            ->where('status', AccountsEnum::PAID->value)
            ->whereYear('payment_date', $year)
            ->select(
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(value) as total')
            )
            ->groupBy('month')
            ->get();

        foreach ($parcelasPagar as $installment) {
            $faturamentoMeses[$installment->month] -= $installment->total;
        }

        return $faturamentoMeses;
    }

    /**
     * Calcula a comparação mensal consolidada de todas as contas
     *
     * @param int $yearInicio
     * @param int $yearFim
     * @param int|null $contaBancariaId
     * @return array
     */
    private function calculateComparativeMonthly(int $yearInicio, int $yearFim, ?int $contaBancariaId = null): array
    {
        $comparacao = [];
        $meses      = [
            1  => 'Janeiro',
            2  => 'Fevereiro',
            3  => 'Março',
            4  => 'Abril',
            5  => 'Maio',
            6  => 'Junho',
            7  => 'Julho',
            8  => 'Agosto',
            9  => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro'
        ];

        foreach ($meses as $numeroMes => $nomeMes) {
            $totaisPorAno = [];

            for ($year = $yearInicio; $year <= $yearFim; $year++) {
                $totaisPorAno[$year] = $this->calculateTotalMonthYear($numeroMes, $year, $contaBancariaId);
            }

            $comparacao[$nomeMes] = [
                'totais_por_ano' => $totaisPorAno,
                'total_periodo'  => array_sum($totaisPorAno)
            ];
        }

        return $comparacao;
    }

    /**
     * Calcula o total de um mês específico em um ano
     *
     * @param int $mes
     * @param int $year
     * @param int|null $contaBancariaId
     * @return float
     */
    private function calculateTotalMonthYear(int $mes, int $year, ?int $contaBancariaId = null): float
    {
        $queryReceber = Installment::whereHasMorph('parcelable', ['App\Models\AccountsReceivable'], function ($query) use ($contaBancariaId) {
            if ($contaBancariaId) {
                $query->where('conta_bancaria_id', $contaBancariaId);
            }
        })
            ->where('status', AccountsEnum::PAID->value)
            ->whereYear('data_de_pagamento', $year)
            ->whereMonth('data_de_pagamento', $mes)
            ->sum('valor');

        $queryPagar = Installment::whereHasMorph('parcelable', ['App\Models\AccountPayable'], function ($query) use ($contaBancariaId) {
            if ($contaBancariaId) {
                $query->where('conta_bancaria_id', $contaBancariaId);
            }
        })
            ->where('status', AccountsEnum::PAID->value)
            ->whereYear('data_de_pagamento', $year)
            ->whereMonth('data_de_pagamento', $mes)
            ->sum('valor');

        return $queryReceber - $queryPagar;
    }

    /**
     * Calcula resumo geral consolidado
     *
     * @param array $dadosPorConta
     * @return array
     */
    private function calculateGeneralSummary(array $dadosPorConta): array
    {
        $resumo = [
            'total_contas_ativas'       => count($dadosPorConta),
            'faturamento_total_periodo' => 0,
            'melhor_ano'                => null,
            'pior_ano'                  => null,
            'media_mensal'              => 0
        ];

        $totaisPorAno = [];

        foreach ($dadosPorConta as $dados) {
            foreach ($dados['faturamento'] as $year => $dadosAno) {
                if (!isset($totaisPorAno[$year])) {
                    $totaisPorAno[$year] = 0;
                }
                $totaisPorAno[$year] += $dadosAno['valor_anual'];
            }
        }

        if (!empty($totaisPorAno)) {
            $resumo['faturamento_total_periodo'] = array_sum($totaisPorAno);
            $resumo['melhor_ano']                = [
                'ano'   => array_keys($totaisPorAno, max($totaisPorAno))[0],
                'valor' => max($totaisPorAno)
            ];
            $resumo['pior_ano']                  = [
                'ano'   => array_keys($totaisPorAno, min($totaisPorAno))[0],
                'valor' => min($totaisPorAno)
            ];
            $resumo['media_mensal']              = $resumo['faturamento_total_periodo'] / (count($totaisPorAno) * 12);
        }

        return $resumo;
    }

    /**
     * Exporta dados para formato adequado para gráficos
     *
     * @param array $dados
     * @return array
     */
    public function formatForChart(array $dados): array
    {
        $labels   = [];
        $datasets = [];

        foreach ($dados['comparacao_mensal'] as $mes => $dadosMes) {
            $labels[] = $mes;

            foreach ($dadosMes['totais_por_ano'] as $year => $valor) {
                if (!isset($datasets[$year])) {
                    $datasets[$year] = [
                        'label' => $year,
                        'data'  => []
                    ];
                }
                $datasets[$year]['data'][] = round($valor, 2);
            }
        }

        return [
            'labels'   => $labels,
            'datasets' => array_values($datasets)
        ];
    }
}
