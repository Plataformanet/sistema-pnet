<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantServiceCategoryController extends Controller
{
    public function categoryList()
    {
        return Inertia::render('tenant/services/categories/list/List');
    }

    public function categoryCreate()
    {
        return Inertia::render('tenant/services/categories/create/Create');
    }

    public function categoryEdit($id)
    {
        // Mock category
        $category = [
            'id' => $id,
            'name' => 'Categoria de Teste',
            'active' => true,
        ];

        return Inertia::render('tenant/services/categories/edit/Edit', [
            'category' => $category
        ]);
    }

    public function categoryUpdate(Request $request, $id)
    {
        // Placeholder
        return redirect()->route('tenant.services.categories.list');
    }
}
