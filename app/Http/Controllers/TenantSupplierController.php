<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Services\ContactService;
use App\Services\SuppliersService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantSupplierController extends Controller
{
    public function __construct(
        protected ContactService $contactService,
        protected SuppliersService $supplierService,
    ) {
    }

    public function index()
    {
        $suppliers = $this->supplierService->findAll();

        return Inertia::render('tenant/registrations/suppliers/list/List', [
            'suppliers' => $suppliers,
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/registrations/suppliers/create/Create');
    }

    public function store(StoreContactRequest $request)
    {
        $tenant = tenant();

        try {
            $contact = $this->contactService->store($request->validated(), $tenant);

            $this->supplierService->store($contact, $request->validated(), $tenant);

            return redirect()->route('tenant.registrations.suppliers.list')->with('success', 'Fornecedor criado com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao criar fornecedor: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao criar fornecedor!');
        }
    }

    public function show($id)
    {
        $supplier = $this->supplierService->findById($id);

        return Inertia::render('tenant/registrations/suppliers/show/Show', [
            'supplier' => $supplier
        ]);
    }

    public function edit($id)
    {
        $supplier = $this->supplierService->findById($id);

        return Inertia::render('tenant/registrations/suppliers/edit/Edit', [
            'supplier' => $supplier
        ]);
    }

    public function update(UpdateContactRequest $request, $id)
    {
        $tenant = tenant();

        try {
            $contact = $this->contactService->update($request->validated(), $tenant, $id);

            $this->supplierService->update($contact, $request->validated(), $tenant);

            return redirect()->route('tenant.registrations.suppliers.list')->with('success', 'Fornecedor atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar fornecedor: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao atualizar fornecedor!');
        }
    }

    public function destroy($id)
    {
        $tenant = tenant();

        try {
            $this->contactService->destroy($tenant, $id);

            return redirect()->route('tenant.registrations.suppliers.list')->with('success', 'Fornecedor excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir fornecedor: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao excluir fornecedor!');
        }
    }
}
