<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceCategoryRequest;
use App\Http\Requests\UpdateServiceCategoryRequest;
use App\Services\ServiceCategoryService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantServiceCategoryController extends Controller
{
    public function __construct(
        protected ServiceCategoryService $serviceCategoryService,
    ) {}

    public function index()
    {
        $categories = $this->serviceCategoryService->findAll(tenant());

        return Inertia::render('tenant/services/categories/list/List', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/services/categories/create/Create');
    }

    public function store(StoreServiceCategoryRequest $request)
    {
        try {
            $this->serviceCategoryService->store($request->validated(), tenant());

            return redirect()->route('tenant.services.categories.list')->with('success', 'Categoria criada com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao criar categoria: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar categoria!');
        }
    }

    public function edit($id)
    {
        $category = $this->serviceCategoryService->findById($id, tenant());

        return Inertia::render('tenant/services/categories/edit/Edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateServiceCategoryRequest $request, $id)
    {
        try {
            $this->serviceCategoryService->update($id, $request->validated(), tenant());

            return redirect()->route('tenant.services.categories.list')->with('success', 'Categoria atualizada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar categoria: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar categoria!');
        }
    }

    public function destroy($id)
    {
        try {
            $this->serviceCategoryService->destroy($id, tenant());

            return redirect()->route('tenant.services.categories.list')->with('success', 'Categoria excluída com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir categoria: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir categoria!');
        }
    }
}
