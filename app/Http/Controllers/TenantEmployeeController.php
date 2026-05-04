<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Services\ContactService;
use App\Services\EmployeesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantEmployeeController extends Controller
{
    public function __construct(
        protected ContactService $contactService,
        protected EmployeesService $employeesService,
    ) {
    }

    public function index()
    {
        return Inertia::render('tenant/registrations/employees/list/List');
    }

    public function create()
    {
        return Inertia::render('tenant/registrations/employees/create/Create');
    }

    public function store(StoreContactRequest $request)
    {
        $tenant = tenant();

        try {
            $contact = $this->contactService->store($request->validated(), $tenant);

            $this->employeesService->store($contact, $request->validated(), $tenant);

            return redirect()->route('tenant.registrations.employees.list')->with('success', 'Funcionário criado com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao criar funcionário: ' . $th->getMessage());
            $contact->delete();
            return redirect()->back()->with('error', 'Erro ao criar funcionário!');
        }
    }

    public function show($id)
    {
        // Mock employee
        $employee = [
            'id'       => $id,
            'type'     => 'PF',
            'name'     => 'Mock Employee',
            'email'    => 'mock@employee.com',
            'cpf'      => '000.000.000-00',
            'position' => 'Mock Position',
            'salary'   => '2500',
        ];

        return Inertia::render('tenant/registrations/employees/show/Show', [
            'employee' => $employee
        ]);
    }

    public function edit($id)
    {
        // Mock employee
        $employee = [
            'id'       => $id,
            'name'     => 'Mock Employee',
            'email'    => 'mock@employee.com',
            'cpf'      => '000.000.000-00',
            'position' => 'Mock Position',
            'salary'   => '2500',
        ];

        return Inertia::render('tenant/registrations/employees/edit/Edit', [
            'employee' => $employee
        ]);
    }

    public function update(Request $request, $id)
    {
        // Placeholder
        return redirect()->route('tenant.registrations.employees.list');
    }

    public function destroy($id)
    {
        // Placeholder
        return redirect()->route('tenant.registrations.employees.list');
    }
}
