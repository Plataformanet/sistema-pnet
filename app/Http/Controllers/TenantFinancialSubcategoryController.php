<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinancialSubcategoryRequest;
use App\Http\Requests\UpdateFinancialSubcategoryRequest;
use App\Services\FinancialCategoryService;
use App\Services\FinancialSubcategoryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TenantFinancialSubcategoryController extends Controller
{
    public function __construct(
        protected FinancialSubcategoryService $financialSubcategoryService,
        protected FinancialCategoryService $financialCategoryService,
    ) {
    }

    public function index()
    {
        $subcategories = $this->financialSubcategoryService->findAll(tenant());

        return Inertia::render('tenant/finance/subcategories/list/List', [
            'subcategories' => $subcategories,
        ]);
    }

    public function create()
    {
        $categories = $this->financialCategoryService->findAll(tenant());

        return Inertia::render('tenant/finance/subcategories/create/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreFinancialSubcategoryRequest $request)
    {
        try {
            $this->financialSubcategoryService->create($request->validated(), tenant());

            return redirect()->route('tenant.finance.subcategories.list')->with('success', 'Subcategoria criada com sucesso!');
        } catch (ValidationException $th) {
            throw $th;
        } catch (\Throwable $th) {
            Log::error('Erro ao criar subcategoria financeira: ' . $th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar subcategoria!');
        }
    }

    public function edit($id)
    {
        $subcategory = $this->financialSubcategoryService->findById($id, tenant());
        $categories  = $this->financialCategoryService->findAll(tenant());

        return Inertia::render('tenant/finance/subcategories/edit/Edit', [
            'subcategory' => $subcategory,
            'categories'  => $categories,
        ]);
    }

    public function update(UpdateFinancialSubcategoryRequest $request, $id)
    {
        try {
            $this->financialSubcategoryService->update($id, $request->validated(), tenant());

            return redirect()->route('tenant.finance.subcategories.list')->with('success', 'Subcategoria atualizada com sucesso!');
        } catch (ValidationException $th) {
            throw $th;
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar subcategoria financeira: ' . $th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar subcategoria!');
        }
    }

    public function destroy($id)
    {
        try {
            $this->financialSubcategoryService->delete($id, tenant());

            return redirect()->route('tenant.finance.subcategories.list')->with('success', 'Subcategoria excluída com sucesso!');
        } catch (ValidationException $th) {
            return redirect()->back()->with('error', $th->validator->errors()->first());
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir subcategoria financeira: ' . $th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir subcategoria!');
        }
    }

    public function byCategoria($categoriaId)
    {
        $subcategorias = $this->financialSubcategoryService->findByCategoriaId($categoriaId, tenant());
        return response()->json($subcategorias);
    }
}
