<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantClientController extends Controller
{
    public function clientList()
    {
        return Inertia::render('tenant/registrations/clients/list/List');
    }
    public function clientCreate()
    {
        return Inertia::render('tenant/registrations/clients/create/Create');
    }

    public function clientEdit($id)
    {
        // Mock client
        $client = [
            'id' => $id,
            'type' => 'PF',
            'name' => 'Mock Client',
            'email' => 'mock@client.com',
            'cpf' => '000.000.000-00',
            'phone' => '(00) 0000-0000',
        ];

        return Inertia::render('tenant/registrations/clients/edit/Edit', [
            'client' => $client
        ]);
    }

    public function clientUpdate(Request $request, $id)
    {
        // Placeholder
        return redirect()->route('tenant.registrations.clients.list');
    }
}
