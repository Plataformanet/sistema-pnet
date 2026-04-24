<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantRoleController extends Controller
{
    public function roleList()
    {
        return Inertia::render('tenant/settings/roles/list/List');
    }
    
    public function roleCreate()
    {
        return Inertia::render('tenant/settings/roles/create/Create');
    }

    public function roleEdit($id)
    {
        // Mock role data
        $role = [
            'id' => $id,
            'name' => 'Administrador',
            // Mocking permissions that are already checked
            'permissions' => [
                'registrations.clients.view',
                'registrations.clients.edit',
                'sales.sales.view'
            ]
        ];

        return Inertia::render('tenant/settings/roles/edit/Edit', [
            'role' => $role
        ]);
    }

    public function roleUpdate(Request $request, $id)
    {
        // Placeholder for update logic
        return redirect()->route('tenant.settings.roles.list');
    }
}
