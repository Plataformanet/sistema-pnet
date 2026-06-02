<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\UserService;
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
}
