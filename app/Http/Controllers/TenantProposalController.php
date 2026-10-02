<?php

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Http\Requests\IndexProposalRequest;
use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalParticularitiesRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Models\Proposal;
use App\Services\ApplicantService;
use App\Services\ProposalQueryService;
use App\Services\ProposalService;
use App\Services\SellerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TenantProposalController extends Controller
{
    public function __construct(
        protected ProposalService $proposalService,
        protected ProposalQueryService $proposalQueryService,
        protected ApplicantService $applicantService,
        protected SellerService $sellerService,
    ) {}

    public function index(IndexProposalRequest $request)
    {
        $filters = $request->validated();

        return Inertia::render('tenant/documents/proposals/list/List', [
            'proposals' => $this->proposalQueryService->paginate($filters, $request->user(), tenant()),
            'statusCounts' => $this->proposalQueryService->statusCounts($request->user(), tenant()),
            'statuses' => ProposalStatus::options(),
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/documents/proposals/create/Create', $this->proposalQueryService->formOptions(tenant()));
    }

    public function store(StoreProposalRequest $request)
    {
        try {
            $proposal = $this->proposalService->create($request->validated(), $request->user(), tenant());

            return redirect()->route('tenant.documents.proposals.edit', $proposal->id)->with('success', 'Proposta criada com sucesso!');
        } catch (ValidationException $th) {
            throw $th;
        } catch (\Throwable $th) {
            Log::error('Erro ao criar proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar proposta!');
        }
    }

    public function show(Request $request, string $id)
    {
        $proposal = $this->proposalQueryService->findForDisplay($id, tenant());

        Gate::authorize('view', $proposal);

        return Inertia::render('tenant/documents/proposals/show/Show', [
            'proposal' => $proposal->setRelation('documents', $this->proposalQueryService->documentsFor($proposal, $request->user())),
            'checklist' => $this->proposalQueryService->checklist($proposal, tenant()),
            'feesTotal' => tenant()->run(fn () => $proposal->feesTotal()),
            'can' => $this->abilities($proposal),
        ]);
    }

    public function edit(Request $request, string $id)
    {
        $proposal = $this->proposalQueryService->findForDisplay($id, tenant());

        Gate::authorize('view', $proposal);

        $abilities = $this->abilities($proposal);

        return Inertia::render('tenant/documents/proposals/edit/Edit', array_merge(
            $this->proposalQueryService->formOptions(tenant(), $proposal),
            $this->proposalQueryService->managementOptions(tenant()),
            [
                'proposal' => $proposal->setRelation('documents', $this->proposalQueryService->documentsFor($proposal, $request->user())),
                'applicants' => $abilities['update'] ? $this->applicantService->findForProposal($id, tenant()) : [],
                'sellers' => $abilities['update'] ? $this->sellerService->findForProposal($id, tenant()) : [],
                'checklist' => $this->proposalQueryService->checklist($proposal, tenant()),
                'feesTotal' => tenant()->run(fn () => $proposal->feesTotal()),
                'can' => $abilities,
            ],
        ));
    }

    public function update(UpdateProposalRequest $request, string $id)
    {
        Gate::authorize('update', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalService->update($request->validated(), $id, tenant());

            return redirect()->route('tenant.documents.proposals.edit', $id)->with('success', 'Proposta atualizada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar proposta!');
        }
    }

    public function updateParticularities(UpdateProposalParticularitiesRequest $request, string $id)
    {
        Gate::authorize('update', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalService->updateParticularities($request->validated('particularities'), $id, tenant());

            return redirect()->route('tenant.documents.proposals.edit', $id)->with('success', 'Particularidades salvas com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao salvar particularidades da proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao salvar particularidades!');
        }
    }

    public function destroy(string $id)
    {
        Gate::authorize('delete', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalService->delete($id, tenant());

            return redirect()->route('tenant.documents.proposals.list')->with('success', 'Proposta excluída com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir proposta!');
        }
    }

    /**
     * @return array<string, bool>
     */
    private function abilities(Proposal $proposal): array
    {
        return collect([
            'update', 'delete', 'manageTimeline', 'manageFinancial', 'viewFinancial',
            'uploadDocument', 'deleteDocument', 'uploadProof', 'print',
        ])->mapWithKeys(fn (string $ability) => [$ability => Gate::allows($ability, $proposal)])->all();
    }
}
