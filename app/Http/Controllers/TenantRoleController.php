<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Services\RoleService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class TenantRoleController extends Controller
{
    public function __construct(protected RoleService $roleService) {}

    public function index()
    {
        $roles = $this->roleService->findAll(tenant());

        return Inertia::render('tenant/settings/roles/list/List', [
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        $systemPermissions = Permission::select('name', 'display_name')->get()->toArray();

        return Inertia::render('tenant/settings/roles/create/Create', [
            'systemPermissions' => $systemPermissions,
        ]);
    }

    public function store(StoreRoleRequest $request)
    {
        try {
            $this->roleService->store($request->validated(), tenant());

            return redirect()->route('tenant.settings.roles.list')->with('success', 'Cargo criado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao criar cargo: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar cargo!');
        }
    }

    public function edit(string $id)
    {
        $role = $this->roleService->findById($id, tenant());

        $systemPermissions = Permission::select('name', 'display_name')->get()->toArray();

        return Inertia::render('tenant/settings/roles/edit/Edit', [
            'role' => $role,
            'systemPermissions' => $systemPermissions,
        ]);
    }

    public function update(UpdateRoleRequest $request, string $id)
    {
        try {
            $this->roleService->update($request->validated(), $id, tenant());

            return redirect()->route('tenant.settings.roles.list')->with('success', 'Cargo atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar cargo: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar cargo!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->roleService->delete($id, tenant());

            return redirect()->route('tenant.settings.roles.list')->with('success', 'Cargo deletado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao deletar cargo: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao deletar cargo!');
        }
    }
}
