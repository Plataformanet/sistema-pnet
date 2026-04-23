<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'name'       => 'create',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name'       => 'edit',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name'       => 'delete',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name'       => 'view',
            'guard_name' => 'web',
        ]);
    }
}
