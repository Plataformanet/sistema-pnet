<?php

namespace App\Http\Controllers;

use App\Enums\BrazilianState;
use App\Http\Requests\IndexCatalogRequest;
use App\Http\Requests\StoreNotaryRequest;
use App\Http\Requests\UpdateNotaryRequest;
use App\Services\NotaryService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantNotaryController extends Controller
{
    public function __construct(
        protected NotaryService $notaryService,
    ) {}

    public function index(IndexCatalogRequest $request)
    {
        $notaries = $this->notaryService->findAll($request->validated(), tenant());

        return Inertia::render('tenant/documents/notaries/list/List', [
            'notaries' => $notaries,
            'filters' => $request->validated(),
            'states' => BrazilianState::options(),
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/documents/notaries/create/Create', [
            'states' => BrazilianState::options(),
        ]);
    }

    public function edit(string $id)
    {
        $notary = $this->notaryService->findById($id, tenant());

        return Inertia::render('tenant/documents/notaries/edit/Edit', [
            'notary' => $notary,
            'states' => BrazilianState::options(),
        ]);
    }

    public function store(StoreNotaryRequest $request)
    {
        try {
            $this->notaryService->store($request->validated(), tenant());

            return redirect()->route('tenant.documents.notaries.list')->with('success', 'Cartório criado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao criar cartório: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar cartório!');
        }
    }

    public function update(UpdateNotaryRequest $request, string $id)
    {
        try {
            $this->notaryService->update($request->validated(), $id, tenant());

            return redirect()->route('tenant.documents.notaries.list')->with('success', 'Cartório atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar cartório: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar cartório!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->notaryService->delete($id, tenant());

            return redirect()->route('tenant.documents.notaries.list')->with('success', 'Cartório excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir cartório: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir cartório!');
        }
    }

    public function restore(string $id)
    {
        try {
            $this->notaryService->restore($id, tenant());

            return redirect()->route('tenant.documents.notaries.list')->with('success', 'Cartório restaurado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao restaurar cartório: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao restaurar cartório!');
        }
    }
}
