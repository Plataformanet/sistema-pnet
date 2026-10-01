<?php

namespace App\Http\Controllers;

use App\Services\FeeCalculationService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TenantFeeCalculationController extends Controller
{
    public function __construct(
        protected FeeCalculationService $feeCalculationService,
    ) {}

    public function show(string $id)
    {
        $calculation = $this->feeCalculationService->findById($id, tenant());

        Gate::authorize('view', $calculation);

        return Inertia::render('tenant/documents/fee-calculator/result/Result', [
            ...$this->feeCalculationService->formProps(
                $calculation->type,
                $calculation->state,
                $calculation->municipality_ibge_code,
                $calculation->municipality_name,
                tenant(),
            ),
            'calculation' => $this->feeCalculationService->present($calculation),
        ]);
    }
}
