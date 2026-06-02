<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinancialCategoryRequest;
use App\Http\Requests\UpdateFinancialCategoryRequest;
use App\Services\FinancialCategoryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TenantFinancialCategoryController extends Controller
{
    public function __construct(
        protected FinancialCategoryService $financialCategoryService,
    ) {}

    public function index()
    {
        $categories = $this->financialCategoryService->findAll(tenant());

        return Inertia::render('tenant/finance/categories/list/List', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/finance/categories/create/Create');
    }

    public function store(StoreFinancialCategoryRequest $request)
    {
        try {
            $this->financialCategoryService->create($request->validated(), tenant());

            return redirect()->route('tenant.finance.categories.list')->with('success', 'Categoria criada com sucesso!');
        } catch (ValidationException $th) {
            throw $th;
        } catch (\Throwable $th) {
            Log::error('Erro ao criar categoria financeira: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar categoria!');
        }
    }

    public function edit($id)
    {
        $category = $this->financialCategoryService->findById($id, tenant());

        return Inertia::render('tenant/finance/categories/edit/Edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateFinancialCategoryRequest $request, $id)
    {
        try {
            $this->financialCategoryService->update($id, $request->validated(), tenant());

            return redirect()->route('tenant.finance.categories.list')->with('success', 'Categoria atualizada com sucesso!');
        } catch (ValidationException $th) {
            throw $th;
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar categoria financeira: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar categoria!');
        }
    }

    public function destroy($id)
    {
        try {
            $this->financialCategoryService->delete($id, tenant());

            return redirect()->route('tenant.finance.categories.list')->with('success', 'Categoria excluída com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir categoria financeira: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir categoria!');
        }
    }
}
