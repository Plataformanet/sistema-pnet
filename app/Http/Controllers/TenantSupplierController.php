<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Services\ContactService;
use App\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantSupplierController extends Controller
{
    public function __construct(
        protected ContactService $contactService,
        protected SupplierService $supplierService,
    ) {
    }

    public function index()
    {
        return Inertia::render('tenant/registrations/suppliers/list/List');
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
            $contact->delete();
            return redirect()->back()->with('error', 'Erro ao criar fornecedor!');
        }
    }

    public function show($id)
    {
        // Mock supplier
        $supplier = [
            'id'               => $id,
            'type'             => 'PJ',
            'corporate_reason' => 'Mock Supplier LTDA',
            'fantasy_name'     => 'Mock Supplier',
            'email'            => 'mock@supplier.com',
            'cnpj'             => '00.000.000/0001-00',
            'contact_name'     => 'Mock Contact',
            'category'         => 'Categoria Mock',
            'phone'            => '(00) 0000-0000',
            'cellphone'        => '(00) 0000-0000',
            'zipcode'          => '00000-000',
            'street'           => 'Rua Mock',
            'number'           => '123',
            'complement'       => 'Mock',
            'neighborhood'     => 'Mock',
            'city'             => 'Mock',
            'state'            => 'Mock',
        ];

        return Inertia::render('tenant/registrations/suppliers/show/Show', [
            'supplier' => $supplier
        ]);
    }

    public function edit($id)
    {
        // Mock supplier
        $supplier = [
            'id'               => $id,
            'type'             => 'PJ',
            'corporate_reason' => 'Mock Supplier LTDA',
            'fantasy_name'     => 'Mock Supplier',
            'email'            => 'mock@supplier.com',
            'cnpj'             => '00.000.000/0001-00',
            'contact_name'     => 'Mock Contact',
            'category'         => 'Categoria Mock',
            'phone'            => '(00) 0000-0000',
            'cellphone'        => '(00) 0000-0000',
            'zipcode'          => '00000-000',
            'street'           => 'Rua Mock',
            'number'           => '123',
            'complement'       => 'Mock',
            'neighborhood'     => 'Mock',
            'city'             => 'Mock',
            'state'            => 'Mock',
        ];

        return Inertia::render('tenant/registrations/suppliers/edit/Edit', [
            'supplier' => $supplier
        ]);
    }

    public function update(Request $request, $id)
    {
        // Placeholder
        return redirect()->route('tenant.registrations.suppliers.list');
    }

    public function destroy($id)
    {
        // Placeholder
        return redirect()->route('tenant.registrations.suppliers.list');
    }
}
