<?php

namespace App\Http\Controllers;

use App\Exceptions\CostItemNotRemovableException;
use App\Http\Requests\StoreProposalCostItemRequest;
use App\Http\Requests\UpdateProposalCostItemRequest;
use App\Http\Requests\UploadCostItemProofRequest;
use App\Services\ProposalCostItemService;
use App\Services\ProposalService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class TenantProposalCostItemController extends Controller
{
    public function __construct(
        protected ProposalCostItemService $proposalCostItemService,
        protected ProposalService $proposalService,
    ) {}

    public function store(StoreProposalCostItemRequest $request, string $id)
    {
        Gate::authorize('manageFinancial', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalCostItemService->storeManual($id, $request->safe()->except('bill'), $request->file('bill'), tenant());

            return redirect()->back()->with('success', 'Lançamento criado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao criar lançamento da proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar lançamento!');
        }
    }

    public function update(UpdateProposalCostItemRequest $request, string $id, string $costItemId)
    {
        Gate::authorize('manageFinancial', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalCostItemService->updateAmount($id, $costItemId, $request->validated('amount'), tenant());

            return redirect()->back()->with('success', 'Valor atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar valor do lançamento: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar valor!');
        }
    }

    public function destroy(string $id, string $costItemId)
    {
        Gate::authorize('manageFinancial', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalCostItemService->delete($id, $costItemId, tenant());

            return redirect()->back()->with('success', 'Lançamento excluído com sucesso!');
        } catch (CostItemNotRemovableException $th) {
            return redirect()->back()->with('warning', $th->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir lançamento da proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir lançamento!');
        }
    }

    public function uploadProof(UploadCostItemProofRequest $request, string $id, string $costItemId)
    {
        Gate::authorize('uploadProof', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalCostItemService->uploadProof($id, $costItemId, $request->file('proof'), tenant());

            return redirect()->back()->with('success', 'Comprovante enviado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao enviar comprovante: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao enviar comprovante!');
        }
    }

    public function downloadBill(string $id, string $costItemId)
    {
        Gate::authorize('viewAttachment', $this->proposalService->findById($id, tenant()));

        return $this->proposalCostItemService->download($id, $costItemId, 'bill', tenant());
    }

    public function downloadProof(string $id, string $costItemId)
    {
        Gate::authorize('viewAttachment', $this->proposalService->findById($id, tenant()));

        return $this->proposalCostItemService->download($id, $costItemId, 'proof', tenant());
    }
}
