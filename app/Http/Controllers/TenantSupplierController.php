<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantSupplierController extends Controller
{
    public function supplierList()
    {
        return Inertia::render('tenant/registrations/suppliers/list/List');
    }

    public function supplierCreate()
    {
        return Inertia::render('tenant/registrations/suppliers/create/Create');
    }

    public function supplierEdit($id)
    {
        // Mock supplier
        $supplier = [
            'id' => $id,
            'type' => 'PJ',
            'corporate_reason' => 'Mock Supplier LTDA',
            'fantasy_name' => 'Mock Supplier',
            'email' => 'mock@supplier.com',
            'cnpj' => '00.000.000/0001-00',
            'contact_name' => 'Mock Contact',
            'category' => 'Categoria Mock',
            'phone' => '(00) 0000-0000',
            'cellphone' => '(00) 0000-0000',
            'zipcode' => '00000-000',
            'street' => 'Rua Mock',
            'number' => '123',
            'complement' => 'Mock',
            'neighborhood' => 'Mock',
            'city' => 'Mock',
            'state' => 'Mock',
        ];

        return Inertia::render('tenant/registrations/suppliers/edit/Edit', [
            'supplier' => $supplier
        ]);
    }

    public function supplierUpdate(Request $request, $id)
    {
        // Placeholder
        return redirect()->route('tenant.registrations.suppliers.list');
    }
}
