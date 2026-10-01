<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexCatalogRequest;
use App\Http\Requests\StorePropertyTypeRequest;
use App\Http\Requests\UpdatePropertyTypeRequest;
use App\Services\PropertyTypeService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantPropertyTypeController extends Controller
{
    public function __construct(
        protected PropertyTypeService $propertyTypeService,
    ) {}

    public function index(IndexCatalogRequest $request)
    {
        $propertyTypes = $this->propertyTypeService->findAll($request->validated(), tenant());

        return Inertia::render('tenant/documents/property-types/list/List', [
            'propertyTypes' => $propertyTypes,
            'filters' => $request->validated(),
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/documents/property-types/create/Create', [
        ]);
    }

    public function edit(string $id)
    {
        $propertyType = $this->propertyTypeService->findById($id, tenant());

        return Inertia::render('tenant/documents/property-types/edit/Edit', [
            'propertyType' => $propertyType,
        ]);
    }

    public function store(StorePropertyTypeRequest $request)
    {
        try {
            $this->propertyTypeService->store($request->validated(), tenant());

            return redirect()->route('tenant.documents.property-types.list')->with('success', 'Tipo de imóvel criado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao criar tipo de imóvel: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar tipo de imóvel!');
        }
    }

    public function update(UpdatePropertyTypeRequest $request, string $id)
    {
        try {
            $this->propertyTypeService->update($request->validated(), $id, tenant());

            return redirect()->route('tenant.documents.property-types.list')->with('success', 'Tipo de imóvel atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar tipo de imóvel: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar tipo de imóvel!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->propertyTypeService->delete($id, tenant());

            return redirect()->route('tenant.documents.property-types.list')->with('success', 'Tipo de imóvel excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir tipo de imóvel: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir tipo de imóvel!');
        }
    }

    public function restore(string $id)
    {
        try {
            $this->propertyTypeService->restore($id, tenant());

            return redirect()->route('tenant.documents.property-types.list')->with('success', 'Tipo de imóvel restaurado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao restaurar tipo de imóvel: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao restaurar tipo de imóvel!');
        }
    }
}
