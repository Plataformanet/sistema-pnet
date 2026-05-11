<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantProductController extends Controller
{
    public function index()
    {
        return Inertia::render('tenant/products/products/list/List');
    }

    public function create()
    {
        return Inertia::render('tenant/products/products/create/Create');
    }

    public function store(Request $request)
    {
        // Placeholder
        return redirect()->route('tenant.products.products.list');
    }

    public function edit($id)
    {
        // Mock product
        $product = [
            'id'              => $id,
            'name'            => 'Mouse Óptico Sem Fio',
            'sku'             => 'INFO-MOU-001',
            'barcode'         => '7891234567890',
            'category_id'     => '1',
            'cost_value'      => 2550, // cents
            'sell_value'      => 6000,
            'manage_stock'    => true,
            'current_stock'   => 150,
            'min_stock'       => 20,
            'unit_of_measure' => 'un',
            'description'     => 'Mouse ergonômico infravermelho de 2.4GHz.',
            'active'          => true,
        ];

        return Inertia::render('tenant/products/products/edit/Edit', [
            'product' => $product
        ]);
    }

    public function update(Request $request, $id)
    {
        // Placeholder
        return redirect()->route('tenant.products.products.list');
    }

    public function destroy($id)
    {
        // Placeholder
        return redirect()->route('tenant.products.products.list');
    }
}
