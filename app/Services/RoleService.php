<?php

namespace App\Services;

use App\Models\Tenant;
use DB;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function store(array $data, Tenant $tenant): Role
    {
        return $tenant->run(function () use ($data) {
            DB::beginTransaction();

            try {
                $role = Role::create(['name' => $data['name']]);

                $role->syncPermissions($data['permissions'] ?? []);

                DB::commit();

                return $role;
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }

    public function update(array $data, string $id, Tenant $tenant): Role
    {
        return $tenant->run(function () use ($data, $id) {
            DB::beginTransaction();

            try {
                $role = Role::findOrFail($id);

                $role->update(['name' => $data['name']]);

                $role->syncPermissions($data['permissions'] ?? []);

                DB::commit();

                return $role;
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }

    public function delete(string $id, Tenant $tenant): bool
    {
        return $tenant->run(function () use ($id) {
            DB::beginTransaction();

            try {
                $role = Role::findOrFail($id);

                $deleted = $role->delete();

                DB::commit();

                return $deleted;
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }

    public function findById(string $id, Tenant $tenant): array
    {
        return $tenant->run(function () use ($id) {
            $role = Role::with('permissions')->findOrFail($id);

            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ];
        });
    }

    public function findAll(Tenant $tenant)
    {
        return $tenant->run(fn () => Role::withCount('users')->get()->map(fn (Role $role) => [
            'id' => $role->id,
            'name' => $role->name,
            'users_count' => $role->users_count,
        ]));
    }
}
