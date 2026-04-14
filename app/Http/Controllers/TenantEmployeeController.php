<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantEmployeeController extends Controller
{
    public function employeeList()
    {
        return Inertia::render('tenant/registrations/employees/list/List');
    }
    
    public function employeeCreate()
    {
        return Inertia::render('tenant/registrations/employees/create/Create');
    }

    public function employeeEdit($id)
    {
        // Mock employee
        $employee = [
            'id' => $id,
            'name' => 'Mock Employee',
            'email' => 'mock@employee.com',
            'cpf' => '000.000.000-00',
            'position' => 'Mock Position',
            'salary' => '2500',
        ];

        return Inertia::render('tenant/registrations/employees/edit/Edit', [
            'employee' => $employee
        ]);
    }

    public function employeeUpdate(Request $request, $id)
    {
        // Placeholder
        return redirect()->route('tenant.registrations.employees.list');
    }
}
