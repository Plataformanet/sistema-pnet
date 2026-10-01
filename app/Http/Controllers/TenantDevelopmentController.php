<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexCatalogRequest;
use App\Http\Requests\StoreDevelopmentRequest;
use App\Http\Requests\UpdateDevelopmentRequest;
use App\Services\DevelopmentService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantDevelopmentController extends Controller
{
    public function __construct(
        protected DevelopmentService $developmentService,
    ) {}

    public function index(IndexCatalogRequest $request)
    {
        $developments = $this->developmentService->findAll($request->validated(), tenant());

        return Inertia::render('tenant/documents/developments/list/List', [
            'developments' => $developments,
            'filters' => $request->validated(),
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/documents/developments/create/Create', [
        ]);
    }

    public function edit(string $id)
    {
        $development = $this->developmentService->findById($id, tenant());

        return Inertia::render('tenant/documents/developments/edit/Edit', [
            'development' => $development,
        ]);
    }

    public function store(StoreDevelopmentRequest $request)
    {
        try {
            $this->developmentService->store($request->validated(), tenant());

            return redirect()->route('tenant.documents.developments.list')->with('success', 'Empreendimento criado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao criar empreendimento: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar empreendimento!');
        }
    }

    public function update(UpdateDevelopmentRequest $request, string $id)
    {
        try {
            $this->developmentService->update($request->validated(), $id, tenant());

            return redirect()->route('tenant.documents.developments.list')->with('success', 'Empreendimento atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar empreendimento: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar empreendimento!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->developmentService->delete($id, tenant());

            return redirect()->route('tenant.documents.developments.list')->with('success', 'Empreendimento excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir empreendimento: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir empreendimento!');
        }
    }

    public function restore(string $id)
    {
        try {
            $this->developmentService->restore($id, tenant());

            return redirect()->route('tenant.documents.developments.list')->with('success', 'Empreendimento restaurado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao restaurar empreendimento: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao restaurar empreendimento!');
        }
    }
}
