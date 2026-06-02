<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryProductRequest;
use App\Http\Requests\UpdateCategoryProductRequest;
use App\Services\CategoryProductService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantProductCategoryController extends Controller
{
    public function __construct(
        protected CategoryProductService $categoryProductService,
    ) {}

    public function index()
    {
        $categories = $this->categoryProductService->findAll(tenant());

        return Inertia::render('tenant/products/categories/list/List', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/products/categories/create/Create');
    }

    public function store(StoreCategoryProductRequest $request)
    {
        try {
            $this->categoryProductService->store($request->validated(), tenant());

            return redirect()->route('tenant.products.categories.list')->with('success', 'Categoria criada com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao criar categoria: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar categoria!');
        }
    }

    public function edit($id)
    {
        $category = $this->categoryProductService->findById($id, tenant());

        return Inertia::render('tenant/products/categories/edit/Edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryProductRequest $request, $id)
    {
        try {
            $this->categoryProductService->update($id, $request->validated(), tenant());

            return redirect()->route('tenant.products.categories.list')->with('success', 'Categoria atualizada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar categoria: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar categoria!');
        }
    }

    public function destroy($id)
    {
        try {
            $this->categoryProductService->destroy($id, tenant());

            return redirect()->route('tenant.products.categories.list')->with('success', 'Categoria excluída com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir categoria: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir categoria!');
        }
    }
}
