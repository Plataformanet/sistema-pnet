<?php

namespace App\Http\Controllers;

use App\Exceptions\ProposalTimelineException;
use App\Http\Requests\CompleteProposalStageRequest;
use App\Services\ProposalService;
use App\Services\ProposalTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class TenantProposalTimelineController extends Controller
{
    public function __construct(
        protected ProposalTimelineService $proposalTimelineService,
        protected ProposalService $proposalService,
    ) {}

    public function start(string $id)
    {
        Gate::authorize('manageTimeline', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalTimelineService->start($id, tenant());

            return redirect()->back()->with('success', 'Acompanhamento iniciado com sucesso!');
        } catch (ProposalTimelineException $th) {
            return redirect()->back()->with('warning', $th->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao iniciar acompanhamento: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao iniciar acompanhamento!');
        }
    }

    public function complete(CompleteProposalStageRequest $request, string $id, string $proposalStageId)
    {
        Gate::authorize('manageTimeline', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalTimelineService->complete(
                $id,
                $proposalStageId,
                $request->safe()->only(['date', 'notes', 'title']),
                $request->file('file'),
                $request->user(),
                tenant(),
            );

            return redirect()->back()->with('success', 'Etapa salva com sucesso!');
        } catch (ProposalTimelineException $th) {
            return redirect()->back()->with('warning', $th->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao concluir etapa: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao concluir etapa!');
        }
    }

    public function restore(Request $request, string $id)
    {
        Gate::authorize('manageTimeline', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalTimelineService->restore($id, tenant());

            return redirect()->back()->with('success', 'Acompanhamento restaurado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao restaurar acompanhamento: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao restaurar acompanhamento!');
        }
    }
}
