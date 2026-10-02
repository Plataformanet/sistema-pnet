<?php

namespace App\Http\Controllers;

use App\Exceptions\LastApplicantException;
use App\Exceptions\PersonAlreadyInProposalException;
use App\Http\Requests\StoreProposalApplicantRequest;
use App\Http\Requests\UpdateProposalApplicantRequest;
use App\Services\ApplicantService;
use App\Services\ProposalService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class TenantProposalApplicantController extends Controller
{
    public function __construct(
        protected ApplicantService $applicantService,
        protected ProposalService $proposalService,
    ) {}

    public function store(StoreProposalApplicantRequest $request, string $id)
    {
        Gate::authorize('update', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalService->addApplicant($request->validated(), $id, tenant());

            return redirect()->back()->with('success', 'Proponente adicionado com sucesso!');
        } catch (PersonAlreadyInProposalException $th) {
            return redirect()->back()->with('warning', $th->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao adicionar proponente à proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao adicionar proponente!');
        }
    }

    public function update(UpdateProposalApplicantRequest $request, string $id, string $applicantId)
    {
        Gate::authorize('update', $this->proposalService->findById($id, tenant()));

        try {
            $this->applicantService->update($request->validated(), $id, $applicantId, tenant());

            return redirect()->back()->with('success', 'Proponente atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar proponente da proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar proponente!');
        }
    }

    public function destroy(string $id, string $applicantId)
    {
        Gate::authorize('update', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalService->removeApplicant($id, $applicantId, tenant());

            return redirect()->back()->with('success', 'Proponente removido da proposta com sucesso!');
        } catch (LastApplicantException $th) {
            return redirect()->back()->with('warning', $th->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao remover proponente da proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao remover proponente!');
        }
    }
}
