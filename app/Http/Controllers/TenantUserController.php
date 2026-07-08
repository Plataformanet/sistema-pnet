<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Permission;
use App\Services\UserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class TenantUserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function index()
    {
        $users = $this->userService->findAll(tenant());

        return Inertia::render('tenant/settings/users/list/List', [
            'users' => $users,
        ]);
    }

    public function create()
    {
        $roles = Role::all()->pluck('name')->toArray();

        return Inertia::render('tenant/settings/users/create/Create', [
            'roles' => $roles,
            'systemPermissions' => $this->systemPermissions(),
            'rolesWithPermissions' => $this->rolesWithPermissions(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        try {
            $this->userService->store($request->validated(), tenant());

            return redirect()->route('tenant.settings.users.list')->with('success', 'Usuário criado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao criar usuário: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar usuário!');
        }
    }

    public function edit($id)
    {
        $user = $this->userService->findById($id, tenant());

        $roles = Role::all()->pluck('name')->toArray();

        return Inertia::render('tenant/settings/users/edit/Edit', [
            'user' => $user,
            'roles' => $roles,
            'role' => $user->getRoleNames()->first(),
            'systemPermissions' => $this->systemPermissions(),
            'rolesWithPermissions' => $this->rolesWithPermissions(),
        ]);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        try {
            $this->userService->update($id, $request->validated(), tenant());

            return redirect()->route('tenant.settings.users.list')->with('success', 'Usuário atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar usuário: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar usuário!');
        }
    }

    public function delete($id)
    {
        try {
            $this->userService->destroy($id, tenant());

            return redirect()->route('tenant.settings.users.list')->with('success', 'Usuário deletado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao deletar usuário: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao deletar usuário!');
        }
    }

    /**
     * Lista todas as permissões do sistema para o formulário de usuário.
     *
     * @return Collection<int, array{name: string, display_name: string}>
     */
    private function systemPermissions()
    {
        return Permission::orderBy('name')->get(['name', 'display_name']);
    }

    /**
     * Mapeia cada cargo para a lista de nomes de permissões que ele possui.
     *
     * @return Collection<string, Collection<int, string>>
     */
    private function rolesWithPermissions()
    {
        return Role::with('permissions:id,name')
            ->get()
            ->mapWithKeys(fn (Role $role) => [$role->name => $role->permissions->pluck('name')]);
    }
}
