<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantProductController extends Controller
{
    public function productList()
    {
        return Inertia::render('tenant/products/products/list/List');
    }

    public function productCreate()
    {
        return Inertia::render('tenant/products/products/create/Create');
    }

    public function productEdit($id)
    {
        // Mock product
        $product = [
            'id' => $id,
            'name' => 'Mouse Óptico Sem Fio',
            'sku' => 'INFO-MOU-001',
            'barcode' => '7891234567890',
            'category_id' => '1',
            'cost_value' => 2550, // cents
            'sell_value' => 6000,
            'manage_stock' => true,
            'current_stock' => 150,
            'min_stock' => 20,
            'unit_of_measure' => 'un',
            'description' => 'Mouse ergonômico infravermelho de 2.4GHz.',
            'active' => true,
        ];

        return Inertia::render('tenant/products/products/edit/Edit', [
            'product' => $product
        ]);
    }

    public function productUpdate(Request $request, $id)
    {
        // Placeholder
        return redirect()->route('tenant.products.products.list');
    }
}
