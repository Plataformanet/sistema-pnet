<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantService
{

    public function store(array $data): Tenant
    {

        $tenant = Tenant::create([
            'name'          => $data['name'],
            'plan_id'       => 1, //$data['plan_id'],
            'is_active'     => true,
            'trial_ends_at' => now()->addDays(30),
        ]);

        try {
            $tenant->domains()->create([
                'domain' => $data['domain'],
            ]);

            $plan            = Plan::find($data['plan_id']);
            $includedModules = $plan->includedModules()->get();

            foreach ($includedModules as $module) {

                // if ($this->canActivateModule($tenant, $module)) {
                $tenant->modules()->attach($module->id, [
                    'is_active'    => true,
                    'activated_at' => now(),
                ]);

                $arrayOfPermissionNames[] = $module->permissions()->pluck('name')->toArray();
                // }

            }

            $roles = [
                'Admin',
                'Seller',
                'Manager',
                'Financial',
                'Partner'
            ];

            $tenant->run(function () use ($data, $arrayOfPermissionNames, $roles) {
                DB::beginTransaction();

                try {

                    $roles = collect($roles)->map(function ($role) {
                        return [
                            'name'       => $role,
                            'guard_name' => 'web',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    });

                    Role::insert($roles->toArray());

                    $roleAdmin = Role::get()->first();

                    $getPermissions = [];
                    foreach ($arrayOfPermissionNames as $permissions) {
                        foreach ($permissions as $permission) {
                            $getPermissions[] = [
                                'name'       => $permission,
                                'guard_name' => 'web',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }

                    Permission::insert($getPermissions);

                    $roleAdmin->givePermissionTo(Permission::all());

                    $user = User::create([
                        'name'     => $data['userName'],
                        'email'    => $data['email'],
                        'password' => Hash::make($data['password']),
                    ]);

                    $user->assignRole($roleAdmin->name);

                    DB::commit();

                } catch (\Throwable $th) {
                    DB::rollBack();
                    throw $th;
                }
            });

        } catch (\Throwable $e) {
            $tenant->delete();
            throw $e;
        }

        return $tenant;

    }

    /**
     * Validar antes de ativar módulo
     */
    public function canActivateModule(Tenant $tenant, Module $module): bool
    {
        // 1. Verificar se módulo está no plano
        $planHasModule = $tenant->plan->modules()
            ->where('modules.id', $module->id)
            ->exists();

        if (!$planHasModule) {
            throw new \Exception("Módulo não disponível no plano atual");
        }

        // 2. Verificar dependências
        if (!$module->canBeActivatedFor($tenant)) {
            $dependencies = $module->dependencies()->pluck('name')->join(', ');
            throw new \Exception("Requer módulos: {$dependencies}");
        }

        // 3. Verificar se não está bloqueado
        if (!$tenant->is_active) {
            throw new \Exception("Tenant bloqueado");
        }

        return true;
    }

    /**
     * Provisionar novo tenant
     */
    public function provision(array $data)
    {
        DB::transaction(function () use ($data) {
            // 1. Criar tenant
            $tenant = Tenant::create([
                'name'          => $data['company_name'],
                'plan_id'       => $data['plan_id'],
                'is_active'     => true,
                'trial_ends_at' => now()->addDays(30),
            ]);

            // 2. Criar domínio
            $tenant->domains()->create([
                'domain'      => $data['subdomain'] . '.seuapp.com',
                'is_primary'  => true,
                'verified_at' => now(),
            ]);

            // 3. Ativar módulos incluídos no plano
            $plan            = Plan::find($data['plan_id']);
            $includedModules = $plan->includedModules;

            foreach ($includedModules as $module) {
                $tenant->modules()->attach($module->id, [
                    'is_active'    => true,
                    'activated_at' => now(),
                ]);
            }

            // 4. Criar banco de dados do tenant
            // (stancl/tenancy faz isso automaticamente)

            return $tenant;
        });
    }
}
