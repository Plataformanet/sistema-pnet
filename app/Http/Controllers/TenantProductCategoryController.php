<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantProductCategoryController extends Controller
{
    public function categoryList()
    {
        return Inertia::render('tenant/products/categories/list/List');
    }

    public function categoryCreate()
    {
        return Inertia::render('tenant/products/categories/create/Create');
    }

    public function categoryEdit($id)
    {
        // Mock category
        $category = [
            'id' => $id,
            'name' => 'Informática / Periféricos',
            'active' => true,
        ];

        return Inertia::render('tenant/products/categories/edit/Edit', [
            'category' => $category
        ]);
    }

    public function categoryUpdate(Request $request, $id)
    {
        // Placeholder
        return redirect()->route('tenant.products.categories.list');
    }
}
