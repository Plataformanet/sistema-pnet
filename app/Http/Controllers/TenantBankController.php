<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexCatalogRequest;
use App\Http\Requests\StoreBankRequest;
use App\Http\Requests\UpdateBankRequest;
use App\Services\BankService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantBankController extends Controller
{
    public function __construct(
        protected BankService $bankService,
    ) {}

    public function index(IndexCatalogRequest $request)
    {
        $banks = $this->bankService->findAll($request->validated(), tenant());

        return Inertia::render('tenant/documents/banks/list/List', [
            'banks' => $banks,
            'filters' => $request->validated(),
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/documents/banks/create/Create', [
        ]);
    }

    public function edit(string $id)
    {
        $bank = $this->bankService->findById($id, tenant());

        return Inertia::render('tenant/documents/banks/edit/Edit', [
            'bank' => $bank,
        ]);
    }

    public function store(StoreBankRequest $request)
    {
        try {
            $this->bankService->store($request->validated(), tenant());

            return redirect()->route('tenant.documents.banks.list')->with('success', 'Banco criado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao criar banco: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar banco!');
        }
    }

    public function update(UpdateBankRequest $request, string $id)
    {
        try {
            $this->bankService->update($request->validated(), $id, tenant());

            return redirect()->route('tenant.documents.banks.list')->with('success', 'Banco atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar banco: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar banco!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->bankService->delete($id, tenant());

            return redirect()->route('tenant.documents.banks.list')->with('success', 'Banco excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir banco: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir banco!');
        }
    }

    public function restore(string $id)
    {
        try {
            $this->bankService->restore($id, tenant());

            return redirect()->route('tenant.documents.banks.list')->with('success', 'Banco restaurado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao restaurar banco: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao restaurar banco!');
        }
    }
}
