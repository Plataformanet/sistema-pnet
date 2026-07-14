<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function store(array $data, Tenant $tenant): User
    {
        return $tenant->run(function () use ($data) {
            DB::beginTransaction();

            try {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'status' => $data['status'] ?? 1,
                ]);

                $user->assignRole($data['role']);

                // Permissões diretas (extras às herdadas do cargo)
                if (array_key_exists('permissions', $data)) {
                    $user->syncPermissions($data['permissions'] ?? []);
                }

                DB::commit();

                return $user;
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }

    public function update(string $id, array $data, Tenant $tenant): User
    {
        return $tenant->run(function () use ($id, $data) {
            DB::beginTransaction();

            try {
                $user = User::find($id);

                $fields = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                ];

                if (! empty($data['password'])) {
                    $fields['password'] = Hash::make($data['password']);
                }

                // isset (e não empty) para que desativar o usuário (false) realmente persista.
                if (isset($data['status'])) {
                    $fields['status'] = $data['status'];
                }

                $user->update($fields);

                if (! empty($data['role'])) {
                    $user->syncRoles([$data['role']]);
                }

                // Permissões diretas (extras às herdadas do cargo).
                // array_key_exists (e não empty) para que desmarcar todas realmente limpe.
                if (array_key_exists('permissions', $data)) {
                    $user->syncPermissions($data['permissions'] ?? []);
                }

                DB::commit();

                return $user;
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }

    public function findById(string $id, Tenant $tenant): User
    {
        return $tenant->run(fn () => User::with('roles')->findOrFail($id));
    }

    public function findAll(Tenant $tenant)
    {
        return $tenant->run(fn () => User::with('roles')->get()->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles->first()?->name,
            'status' => $user->status,
        ]));
    }

    public function destroy(string $id, Tenant $tenant): void
    {
        $tenant->run(function () use ($id) {
            DB::beginTransaction();

            try {
                User::findOrFail($id)->delete();
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }
}
