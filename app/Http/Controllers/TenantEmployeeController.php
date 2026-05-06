<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
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
        $employees = $this->employeesService->findAll();

        return Inertia::render('tenant/registrations/employees/list/List', [
            'employees' => $employees
        ]);
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
            return redirect()->back()->with('error', 'Erro ao criar funcionário!');
        }
    }

    public function show($id)
    {
        $employee = $this->employeesService->findById($id);

        return Inertia::render('tenant/registrations/employees/show/Show', [
            'employee' => $employee
        ]);
    }

    public function edit($id)
    {
        $employee = $this->employeesService->findById($id);

        return Inertia::render('tenant/registrations/employees/edit/Edit', [
            'employee' => $employee
        ]);
    }

    public function update(UpdateContactRequest $request, $id)
    {
        $tenant = tenant();

        try {
            $contact = $this->contactService->update($request->validated(), $tenant, $id);

            $this->employeesService->update($contact, $request->validated(), $tenant);

            return redirect()->route('tenant.registrations.employees.list')->with('success', 'Funcionário atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar funcionário: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao atualizar funcionário!');
        }
    }

    public function destroy($id)
    {
        $tenant = tenant();

        try {
            $this->contactService->destroy($tenant, $id);

            return redirect()->route('tenant.registrations.employees.list')->with('success', 'Funcionário excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir funcionário: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao excluir funcionário!');
        }
    }
}
