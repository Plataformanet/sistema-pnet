<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'name' => 'Financeiro',
                'slug' => 'finance',
                'description' => 'Gerenciamento financeiro',
                'icon' => '',
                'is_core' => true,
                'requires_modules' => null,
                'route_prefix' => 'financial',
            ],
            [
                'name' => 'Documentações',
                'slug' => 'documents',
                'description' => 'Criação de propostas',
                'icon' => '',
                'is_core' => true,
                'requires_modules' => null,
                'route_prefix' => 'documents',
            ],
        ];

        foreach ($modules as $module) {
            Module::create($module);
        }
    }
}
