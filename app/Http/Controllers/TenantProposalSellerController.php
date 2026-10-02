<?php

namespace App\Http\Controllers;

use App\Exceptions\PersonAlreadyInProposalException;
use App\Http\Requests\StoreProposalSellerRequest;
use App\Http\Requests\UpdateProposalSellerRequest;
use App\Services\ProposalService;
use App\Services\SellerService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class TenantProposalSellerController extends Controller
{
    public function __construct(
        protected SellerService $sellerService,
        protected ProposalService $proposalService,
    ) {}

    public function store(StoreProposalSellerRequest $request, string $id)
    {
        Gate::authorize('update', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalService->addSeller($request->validated(), $id, $request->user(), tenant());

            return redirect()->back()->with('success', 'Vendedor adicionado com sucesso!');
        } catch (PersonAlreadyInProposalException $th) {
            return redirect()->back()->with('warning', $th->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao adicionar vendedor à proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao adicionar vendedor!');
        }
    }

    public function update(UpdateProposalSellerRequest $request, string $id, string $sellerId)
    {
        Gate::authorize('update', $this->proposalService->findById($id, tenant()));

        try {
            $this->sellerService->update($request->validated(), $id, $sellerId, tenant());

            return redirect()->back()->with('success', 'Vendedor atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar vendedor da proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar vendedor!');
        }
    }

    public function destroy(string $id, string $sellerId)
    {
        Gate::authorize('update', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalService->removeSeller($id, $sellerId, tenant());

            return redirect()->back()->with('success', 'Vendedor removido da proposta com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao remover vendedor da proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao remover vendedor!');
        }
    }
}
