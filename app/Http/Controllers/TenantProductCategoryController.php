<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Services\ProductCategoryService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantProductCategoryController extends Controller
{
    public function __construct(
        protected ProductCategoryService $productCategoryService,
    ) {}

    public function index()
    {
        $categories = $this->productCategoryService->findAll(tenant());

        return Inertia::render('tenant/products/categories/list/List', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/products/categories/create/Create');
    }

    public function store(StoreProductCategoryRequest $request)
    {
        try {
            $this->productCategoryService->store($request->validated(), tenant());

            return redirect()->route('tenant.products.categories.list')->with('success', 'Categoria criada com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao criar categoria: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar categoria!');
        }
    }

    public function edit($id)
    {
        $category = $this->productCategoryService->findById($id, tenant());

        return Inertia::render('tenant/products/categories/edit/Edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, $id)
    {
        try {
            $this->productCategoryService->update($id, $request->validated(), tenant());

            return redirect()->route('tenant.products.categories.list')->with('success', 'Categoria atualizada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar categoria: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar categoria!');
        }
    }

    public function destroy($id)
    {
        try {
            $this->productCategoryService->destroy($id, tenant());

            return redirect()->route('tenant.products.categories.list')->with('success', 'Categoria excluída com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir categoria: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir categoria!');
        }
    }
}
