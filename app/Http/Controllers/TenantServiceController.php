<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantServiceController extends Controller
{
    public function serviceList()
    {
        return Inertia::render('tenant/services/services/list/List');
    }

    public function serviceCreate()
    {
        return Inertia::render('tenant/services/services/create/Create');
    }

    public function serviceEdit($id)
    {
        // Mock service
        $service = [
            'id' => $id,
            'name' => 'Mock Service',
            'sku' => 'SRV-001',
            'cost_value' => 5000,
            'sell_value' => 15000,
            'fees' => 1000,
            'category_id' => '1',
            'description' => 'Serviço de teste mockado.',
            'duration' => '60',
            'active' => true,
        ];

        return Inertia::render('tenant/services/services/edit/Edit', [
            'service' => $service
        ]);
    }

    public function serviceUpdate(Request $request, $id)
    {
        // Placeholder
        return redirect()->route('tenant.services.services.list');
    }
}
