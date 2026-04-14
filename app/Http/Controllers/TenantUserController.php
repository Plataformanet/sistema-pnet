<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantUserController extends Controller
{
    public function userList()
    {
        return Inertia::render('tenant/registrations/users/list/List');
    }
    
    public function userCreate()
    {
        return Inertia::render('tenant/registrations/users/create/Create');
    }

    public function userEdit($id)
    {
        // Mock user
        $user = [
            'id' => $id,
            'name' => 'Mock User',
            'email' => 'mock@user.com',
            'role' => 'Administrador',
            'active' => true,
        ];

        return Inertia::render('tenant/registrations/users/edit/Edit', [
            'user' => $user
        ]);
    }

    public function userUpdate(Request $request, $id)
    {
        // Placeholder
        return redirect()->route('tenant.registrations.users.list');
    }
}
