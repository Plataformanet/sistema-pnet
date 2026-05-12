<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\CategoryProductService;
use App\Services\ProductService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantProductController extends Controller
{

    public function __construct(
        protected ProductService $productService,
        protected CategoryProductService $categoryProductService
    ) {
    }

    public function index()
    {
        $products = $this->productService->findAll(tenant());

        return Inertia::render('tenant/products/products/list/List', compact('products'));
    }

    public function create()
    {
        $categories = $this->categoryProductService->findAll(tenant());

        return Inertia::render('tenant/products/products/create/Create', [
            'categories' => $categories->toArray(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $this->productService->store($request->validated(), tenant());

            return redirect()->route('tenant.products.products.list')->with('success', 'Produto criado com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao criar produto: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao criar produto!');
        }
    }

    public function edit(string $id)
    {
        $categories = $this->categoryProductService->findAll(tenant());

        $product = $this->productService->findById($id, tenant());

        return Inertia::render('tenant/products/products/edit/Edit', [
            'product'    => $product,
            'categories' => $categories->toArray(),
        ]);
    }

    public function update(UpdateProductRequest $request, string $id)
    {
        try {
            $this->productService->update($request->validated(), $id, tenant());

            return redirect()->route('tenant.products.products.list')->with('success', 'Produto atualizado com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar produto: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao atualizar produto!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->productService->delete($id, tenant());

            return redirect()->route('tenant.products.products.list')->with('success', 'Produto excluído com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao excluir produto: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao excluir produto!');
        }
    }
}
