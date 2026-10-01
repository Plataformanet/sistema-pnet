<?php

namespace App\Http\Controllers;

use App\Actions\FeeCalculator\AttachFeeEstimateToProposal;
use App\Exceptions\ProposalAlreadyHasFeeEstimateException;
use App\Http\Requests\AttachFeeEstimateRequest;
use App\Services\BillableServiceService;
use App\Services\FeeCalculationService;
use App\Services\ProposalService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantProposalFeeEstimateController extends Controller
{
    public function __construct(
        protected FeeCalculationService $feeCalculationService,
        protected BillableServiceService $billableServiceService,
        protected ProposalService $proposalService,
        protected AttachFeeEstimateToProposal $attachFeeEstimateToProposal,
    ) {}

    public function create(string $id)
    {
        $calculation = $this->feeCalculationService->findById($id, tenant());

        Gate::authorize('view', $calculation);

        return Inertia::render('tenant/documents/fee-calculator/attach/AttachToProposal', [
            'calculation' => $this->feeCalculationService->present($calculation),
            'services' => $this->billableServiceService->options(tenant())->map->only(['id', 'name', 'description', 'price', 'generates_receipt']),
        ]);
    }

    public function store(AttachFeeEstimateRequest $request, string $id)
    {
        $calculation = $this->feeCalculationService->findById($id, tenant());

        Gate::authorize('view', $calculation);
        Gate::authorize('manageFinancial', $this->proposalService->findById((string) $request->validated('proposal_id'), tenant()));

        try {
            $proposal = $this->attachFeeEstimateToProposal->handle(
                $calculation,
                (string) $request->validated('proposal_id'),
                $request->validated('services') ?? [],
                tenant(),
            );

            return redirect()->route('tenant.documents.proposals.edit', $proposal->id)
                ->with('success', __('fee_calculator.messages.fee_estimate_attached', [], 'pt_BR'));
        } catch (ProposalAlreadyHasFeeEstimateException $th) {
            return redirect()->back()->with('warning', $th->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao vincular emolumento à proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao vincular emolumento à proposta!');
        }
    }
}
