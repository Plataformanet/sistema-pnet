<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryServiceRequest;
use App\Http\Requests\UpdateCategoryServiceRequest;
use App\Services\CategoryServicesService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantServiceCategoryController extends Controller
{
    public function __construct(
        protected CategoryServicesService $categoryServicesService,
    ) {}

    public function index()
    {
        $categories = $this->categoryServicesService->findAll(tenant());

        return Inertia::render('tenant/services/categories/list/List', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/services/categories/create/Create');
    }

    public function store(StoreCategoryServiceRequest $request)
    {
        try {
            $this->categoryServicesService->store($request->validated(), tenant());

            return redirect()->route('tenant.services.categories.list')->with('success', 'Categoria criada com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao criar categoria: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar categoria!');
        }
    }

    public function edit($id)
    {
        $category = $this->categoryServicesService->findById($id, tenant());

        return Inertia::render('tenant/services/categories/edit/Edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryServiceRequest $request, $id)
    {
        try {
            $this->categoryServicesService->update($id, $request->validated(), tenant());

            return redirect()->route('tenant.services.categories.list')->with('success', 'Categoria atualizada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar categoria: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar categoria!');
        }
    }

    public function destroy($id)
    {
        try {
            $this->categoryServicesService->destroy($id, tenant());

            return redirect()->route('tenant.services.categories.list')->with('success', 'Categoria excluída com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir categoria: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir categoria!');
        }
    }
}
