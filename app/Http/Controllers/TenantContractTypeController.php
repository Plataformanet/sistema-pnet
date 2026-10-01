<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexCatalogRequest;
use App\Http\Requests\StoreContractTypeRequest;
use App\Http\Requests\UpdateContractTypeRequest;
use App\Services\ContractTypeService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantContractTypeController extends Controller
{
    public function __construct(
        protected ContractTypeService $contractTypeService,
    ) {}

    public function index(IndexCatalogRequest $request)
    {
        $contractTypes = $this->contractTypeService->findAll($request->validated(), tenant());

        return Inertia::render('tenant/documents/contract-types/list/List', [
            'contractTypes' => $contractTypes,
            'filters' => $request->validated(),
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/documents/contract-types/create/Create', [
        ]);
    }

    public function edit(string $id)
    {
        $contractType = $this->contractTypeService->findById($id, tenant());

        return Inertia::render('tenant/documents/contract-types/edit/Edit', [
            'contractType' => $contractType,
        ]);
    }

    public function store(StoreContractTypeRequest $request)
    {
        try {
            $this->contractTypeService->store($request->validated(), tenant());

            return redirect()->route('tenant.documents.contract-types.list')->with('success', 'Tipo de contrato criado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao criar tipo de contrato: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar tipo de contrato!');
        }
    }

    public function update(UpdateContractTypeRequest $request, string $id)
    {
        try {
            $this->contractTypeService->update($request->validated(), $id, tenant());

            return redirect()->route('tenant.documents.contract-types.list')->with('success', 'Tipo de contrato atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar tipo de contrato: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar tipo de contrato!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->contractTypeService->delete($id, tenant());

            return redirect()->route('tenant.documents.contract-types.list')->with('success', 'Tipo de contrato excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir tipo de contrato: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir tipo de contrato!');
        }
    }

    public function restore(string $id)
    {
        try {
            $this->contractTypeService->restore($id, tenant());

            return redirect()->route('tenant.documents.contract-types.list')->with('success', 'Tipo de contrato restaurado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao restaurar tipo de contrato: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao restaurar tipo de contrato!');
        }
    }
}
