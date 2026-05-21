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

                if (!empty($data['password'])) {
                    $fields['password'] = Hash::make($data['password']);
                }

                $user->update($fields);

                if (!empty($data['role'])) {
                    $user->syncRoles([$data['role']]);
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
        return $tenant->run(fn() => User::with('roles')->findOrFail($id));
    }

    public function findAll(Tenant $tenant)
    {
        return $tenant->run(fn() => User::with('roles')->get()->map(fn(User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles->first()?->name,
            'status' => $user->status,
        ]));
    }

    public function destroy(User $user, Tenant $tenant): void
    {
        $tenant->run(function () use ($user) {
            DB::beginTransaction();

            try {
                $user->delete();
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }
}
