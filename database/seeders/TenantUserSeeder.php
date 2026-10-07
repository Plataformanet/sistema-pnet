<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\User;
use Hash;
use Illuminate\Database\Seeder;

class TenantUserSeeder extends Seeder
{
    /**
     * Run the database seeds. Depende dos cargos criados pelo
     * `tenants:sync-permissions`, que precisa rodar antes.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Usuário Teste',
            'email' => 'usuarioteste@teste.com',
            'password' => Hash::make('12345678'),
        ])->assignRole(RolesEnum::ADMIN->label());
    }
}
