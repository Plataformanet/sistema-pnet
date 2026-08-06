<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            [
                'name' => 'settings.company.view',
                'display_name' => 'Empresa (Visualizar)',
                'guard_name' => 'web',
            ],
            [
                'name' => 'settings.company.edit',
                'display_name' => 'Empresa (Editar)',
                'guard_name' => 'web',
            ],
        ];

        foreach ($permissions as $permData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permData['name'], 'guard_name' => $permData['guard_name']],
                ['display_name' => $permData['display_name']]
            );

            // Concede as permissões para as roles existentes (especialmente Admin)
            $roles = Role::all();
            foreach ($roles as $role) {
                if (! $role->hasPermissionTo($permission->name)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', ['settings.company.view', 'settings.company.edit'])->delete();
    }
};
