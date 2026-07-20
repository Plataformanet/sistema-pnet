<?php

namespace App\Http\Controllers;

use App\Exceptions\ContactHasFinancialEntriesException;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Contact;
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
    ) {}

    public function index()
    {
        $employees = $this->employeesService->findAll(tenant());

        return Inertia::render('tenant/registrations/employees/list/List', [
            'employees' => $employees->toArray(),
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/registrations/employees/create/Create');
    }

    public function store(StoreEmployeeRequest $request)
    {
        try {

            $contact = Contact::where('cpf_cnpj', $request->validated('cpf_cnpj'))->first();

            if (! $contact) {
                $contact = $this->contactService->store($request->validated(), tenant());
            }

            $this->employeesService->store($contact, $request->validated(), tenant());

            return redirect()->route('tenant.registrations.employees.list')->with('success', 'Funcionário criado com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao criar funcionário: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar funcionário!');
        }
    }

    public function show(string $id)
    {
        $employee = $this->employeesService->findByContactId($id, tenant());

        return Inertia::render('tenant/registrations/employees/show/Show', [
            'employee' => $employee,
        ]);
    }

    public function edit(string $id)
    {
        $employee = $this->employeesService->findByContactId($id, tenant());

        return Inertia::render('tenant/registrations/employees/edit/Edit', [
            'employee' => $employee->toArray(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, string $id)
    {
        try {
            $contact = $this->contactService->update($request->validated(), tenant(), $id);

            $this->employeesService->update($contact, $request->validated(), tenant());

            return redirect()->route('tenant.registrations.employees.list')->with('success', 'Funcionário atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar funcionário: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar funcionário!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->employeesService->destroy(tenant(), $id);

            return redirect()->route('tenant.registrations.employees.list')->with('success', 'Funcionário excluído com sucesso!');
        } catch (ContactHasFinancialEntriesException $th) {
            return redirect()->back()->with('warning', $th->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir funcionário: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir funcionário!');
        }
    }

    public function toggleActive(Request $request, string $id)
    {
        try {
            $active = $this->employeesService->setActive(tenant(), $id, $request->boolean('active'));

            $message = $active ? 'Funcionário ativado com sucesso!' : 'Funcionário inativado com sucesso!';

            return redirect()->route('tenant.registrations.employees.list')->with('success', $message);
        } catch (\Throwable $th) {
            Log::error('Erro ao alterar status do funcionário: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao alterar status do funcionário!');
        }
    }

    public function getContactByCpfCnpj(string $cpfCnpj)
    {
        try {
            $contact = $this->contactService->getContactByCpfCnpj($cpfCnpj, tenant());

            return response()->json($contact);
        } catch (\Throwable $th) {
            Log::error('Erro ao buscar contato: '.$th->getMessage());

            return response()->json([
                'message' => 'Erro ao buscar contato!',
            ], 500);
        }
    }
}
