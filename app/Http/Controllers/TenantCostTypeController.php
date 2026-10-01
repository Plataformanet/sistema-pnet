<?php

namespace App\Http\Controllers;

use App\Enums\ReceiptType;
use App\Http\Requests\IndexCatalogRequest;
use App\Http\Requests\StoreCostTypeRequest;
use App\Http\Requests\UpdateCostTypeRequest;
use App\Services\CostTypeService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantCostTypeController extends Controller
{
    public function __construct(
        protected CostTypeService $costTypeService,
    ) {}

    public function index(IndexCatalogRequest $request)
    {
        $costTypes = $this->costTypeService->findAll($request->validated(), tenant());

        return Inertia::render('tenant/documents/cost-types/list/List', [
            'costTypes' => $costTypes,
            'filters' => $request->validated(),
            'receiptTypes' => ReceiptType::options(ReceiptType::forCostTypes()),
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/documents/cost-types/create/Create', [
            'receiptTypes' => ReceiptType::options(ReceiptType::forCostTypes()),
        ]);
    }

    public function edit(string $id)
    {
        $costType = $this->costTypeService->findById($id, tenant());

        return Inertia::render('tenant/documents/cost-types/edit/Edit', [
            'costType' => $costType,
            'receiptTypes' => ReceiptType::options(ReceiptType::forCostTypes()),
        ]);
    }

    public function store(StoreCostTypeRequest $request)
    {
        try {
            $this->costTypeService->store($request->validated(), tenant());

            return redirect()->route('tenant.documents.cost-types.list')->with('success', 'Tipo de custo criado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao criar tipo de custo: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar tipo de custo!');
        }
    }

    public function update(UpdateCostTypeRequest $request, string $id)
    {
        try {
            $this->costTypeService->update($request->validated(), $id, tenant());

            return redirect()->route('tenant.documents.cost-types.list')->with('success', 'Tipo de custo atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar tipo de custo: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar tipo de custo!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->costTypeService->delete($id, tenant());

            return redirect()->route('tenant.documents.cost-types.list')->with('success', 'Tipo de custo excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir tipo de custo: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir tipo de custo!');
        }
    }

    public function restore(string $id)
    {
        try {
            $this->costTypeService->restore($id, tenant());

            return redirect()->route('tenant.documents.cost-types.list')->with('success', 'Tipo de custo restaurado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao restaurar tipo de custo: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao restaurar tipo de custo!');
        }
    }
}
