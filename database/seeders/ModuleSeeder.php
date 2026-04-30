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
                'name'             => 'Cadastros',
                'slug'             => 'registrations',
                'description'      => 'Gerenciamento de cadastros',
                'icon'             => '',
                'is_core'          => true,
                'requires_modules' => null,
                'route_prefix'     => 'registrations',
            ],
            [
                'name'             => 'Vendas',
                'slug'             => 'sales',
                'description'      => 'Gerenciamento de vendas',
                'icon'             => '',
                'is_core'          => true,
                'requires_modules' => null,
                'route_prefix'     => 'sales',
            ],
            [
                'name'             => 'Serviços',
                'slug'             => 'services',
                'description'      => 'Gerenciamento de serviços',
                'icon'             => '',
                'is_core'          => true,
                'requires_modules' => null,
                'route_prefix'     => 'services',
            ],
            [
                'name'             => 'Produtos',
                'slug'             => 'products',
                'description'      => 'Gerenciamento de produtos',
                'icon'             => '',
                'is_core'          => true,
                'requires_modules' => null,
                'route_prefix'     => 'products',
            ],
            [
                'name'             => 'Financeiro',
                'slug'             => 'finance',
                'description'      => 'Gerenciamento financeiro',
                'icon'             => '',
                'is_core'          => true,
                'requires_modules' => null,
                'route_prefix'     => 'financial',
            ],
            [
                'name'             => 'Documentações',
                'slug'             => 'documents',
                'description'      => 'Criação de propostas',
                'icon'             => '',
                'is_core'          => true,
                'requires_modules' => null,
                'route_prefix'     => 'documents',
            ],
        ];

        foreach ($modules as $module) {
            Module::create($module);
        }
    }
}
