<?php

namespace App\Http\Controllers;

use App\Exceptions\StageOrderTakenException;
use App\Http\Requests\CreateStageRequest;
use App\Http\Requests\IndexStageRequest;
use App\Http\Requests\MoveStageRequest;
use App\Http\Requests\StoreStageRequest;
use App\Http\Requests\UpdateStageRequest;
use App\Services\StageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantStageController extends Controller
{
    public function __construct(
        protected StageService $stageService,
    ) {}

    public function index(IndexStageRequest $request)
    {
        $bankId = $request->filled('bank_id') ? (int) $request->validated('bank_id') : null;
        $stages = $this->stageService->findAll($request->validated(), tenant());

        return Inertia::render('tenant/documents/stages/list/List', [
            'stages' => $stages,
            'filters' => $request->validated(),
            'bankId' => $bankId,
            'timelines' => $this->stageService->timelineOptions(tenant(), $bankId),
        ]);
    }

    public function create(CreateStageRequest $request)
    {
        $bankId = $request->validated('bank_id');
        $bank = $this->stageService->findTimelineBank($bankId !== null ? (int) $bankId : null, tenant());

        return Inertia::render('tenant/documents/stages/create/Create', [
            'bankId' => $bank?->id,
            'timelineName' => $bank?->name ?? StageService::DEFAULT_TIMELINE_NAME,
        ]);
    }

    public function edit(string $id)
    {
        $stage = $this->stageService->findById($id, tenant());

        return Inertia::render('tenant/documents/stages/edit/Edit', [
            'stage' => $stage,
            'timelineName' => $stage->bank?->name ?? StageService::DEFAULT_TIMELINE_NAME,
            'hasOpenProposalStages' => $this->stageService->hasOpenProposalStages($id, tenant()),
        ]);
    }

    public function store(StoreStageRequest $request)
    {
        try {
            $stage = $this->stageService->store($request->validated(), tenant());

            return $this->redirectToTimeline($stage->bank_id)->with('success', 'Etapa criada com sucesso!');
        } catch (StageOrderTakenException $th) {
            return redirect()->back()->with('warning', $th->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao criar etapa: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar etapa!');
        }
    }

    public function update(UpdateStageRequest $request, string $id)
    {
        try {
            $bankId = $this->stageService->timelineBankId($id, tenant());
            $this->stageService->update($request->validated(), $id, tenant());

            return $this->redirectToTimeline($bankId)->with('success', 'Etapa atualizada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar etapa: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar etapa!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $bankId = $this->stageService->timelineBankId($id, tenant());
            $this->stageService->delete($id, tenant());

            return $this->redirectToTimeline($bankId)->with('success', 'Etapa excluída com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir etapa: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir etapa!');
        }
    }

    public function restore(string $id)
    {
        try {
            $bankId = $this->stageService->timelineBankId($id, tenant());
            $this->stageService->restore($id, tenant());

            return $this->redirectToTimeline($bankId)->with('success', 'Etapa restaurada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao restaurar etapa: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao restaurar etapa!');
        }
    }

    public function move(MoveStageRequest $request, string $id)
    {
        try {
            if ($request->filled('target_id')) {
                $this->stageService->moveTo($id, (string) $request->validated('target_id'), tenant());
            } else {
                $this->stageService->move($id, $request->validated('direction'), tenant());
            }

            // Volta para a mesma página/filtro da listagem de onde a etapa foi movida.
            return redirect()->back()->with('success', 'Ordem das etapas atualizada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao reordenar etapa: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao reordenar etapa!');
        }
    }

    /**
     * Volta para a listagem da timeline da etapa (a padrão omite o banco).
     */
    private function redirectToTimeline(?int $bankId): RedirectResponse
    {
        return redirect()->route('tenant.documents.stages.list', array_filter(['bank_id' => $bankId]));
    }
}
