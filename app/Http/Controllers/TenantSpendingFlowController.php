<?php

namespace App\Http\Controllers;

use App\Enums\FinancialCategoryEnum;
use App\Models\FinancialCategory;
use App\Services\SpendingFlowService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PDF;

class TenantSpendingFlowController extends Controller
{
    public function __construct(public SpendingFlowService $spendingFlowService) {}

    /**
     * Handle the incoming request.
     */
    public function index(Request $request)
    {

        $year = $request->input('year', now()->year);
        $category_id = $request->input('category_id', null);

        $financialCategories = FinancialCategory::where('type', FinancialCategoryEnum::EXPENSE)->get()->toArray();

        $months = $this->months();

        $spendingFlow = $this->spendingFlowService->calculateSpendingFlow(tenant(), $category_id, $year);

        return Inertia::render('tenant/finance/spending-flow/list/List', [
            'year' => $year,
            'categoryId' => $category_id,
            'months' => $months,
            'spendingFlow' => $spendingFlow,
            'financialCategories' => $financialCategories,
        ]);
    }

    public function geraPDF(Request $request)
    {
        $year = $request->input('year', now()->year);
        $category_id = $request->input('category_id', null);

        $months = $this->months();

        $spendingFlow = $this->spendingFlowService->calculateSpendingFlow(tenant(), $category_id, $year);

        $pdf = PDF::loadView(
            'app.financeiro.fluxo_de_gastos.pdf.info-fluxo-de-gastos',
            [
                'spendingFlow' => $spendingFlow,
                'meses' => $months,
            ]
        );
        $pdf->setOption(['dpi' => 140, 'defaultFont' => 'sans-serif']);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream("fluxo-de-gastos-{$year}.pdf");
    }

    public function months()
    {
        return [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];
    }
}
