<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexCatalogRequest;
use App\Http\Requests\MoveStageRequest;
use App\Http\Requests\StoreStageRequest;
use App\Http\Requests\UpdateStageRequest;
use App\Services\StageService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantStageController extends Controller
{
    public function __construct(
        protected StageService $stageService,
    ) {}

    public function index(IndexCatalogRequest $request)
    {
        $stages = $this->stageService->findAll($request->validated(), tenant());

        return Inertia::render('tenant/documents/stages/list/List', [
            'stages' => $stages,
            'filters' => $request->validated(),
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/documents/stages/create/Create', [
        ]);
    }

    public function edit(string $id)
    {
        $stage = $this->stageService->findById($id, tenant());

        return Inertia::render('tenant/documents/stages/edit/Edit', [
            'stage' => $stage,
            'hasOpenProposalStages' => $this->stageService->hasOpenProposalStages($id, tenant()),
        ]);
    }

    public function store(StoreStageRequest $request)
    {
        try {
            $this->stageService->store($request->validated(), tenant());

            return redirect()->route('tenant.documents.stages.list')->with('success', 'Etapa criada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao criar etapa: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar etapa!');
        }
    }

    public function update(UpdateStageRequest $request, string $id)
    {
        try {
            $this->stageService->update($request->validated(), $id, tenant());

            return redirect()->route('tenant.documents.stages.list')->with('success', 'Etapa atualizada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar etapa: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar etapa!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->stageService->delete($id, tenant());

            return redirect()->route('tenant.documents.stages.list')->with('success', 'Etapa excluída com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir etapa: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir etapa!');
        }
    }

    public function restore(string $id)
    {
        try {
            $this->stageService->restore($id, tenant());

            return redirect()->route('tenant.documents.stages.list')->with('success', 'Etapa restaurada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao restaurar etapa: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao restaurar etapa!');
        }
    }

    public function move(MoveStageRequest $request, string $id)
    {
        try {
            $this->stageService->move($id, $request->validated('direction'), tenant());

            return redirect()->route('tenant.documents.stages.list')->with('success', 'Ordem das etapas atualizada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao reordenar etapa: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao reordenar etapa!');
        }
    }
}
