<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $permissions = [

            'registrations.clients.view',
            'registrations.clients.update',
            'registrations.clients.create',
            'registrations.clients.delete',

            'registrations.suppliers.view',
            'registrations.suppliers.update',
            'registrations.suppliers.create',
            'registrations.suppliers.delete',


            'registrations.employees.view',
            'registrations.employees.update',
            'registrations.employees.create',
            'registrations.employees.delete',

            'registrations.users.view',
            'registrations.users.update',
            'registrations.users.create',
            'registrations.users.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
