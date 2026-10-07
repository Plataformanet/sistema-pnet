<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registra no catálogo central os módulos core do sistema. Substitui o antigo
 * ModuleSeeder: as permissões dependem destes módulos (`permissions.module_id`)
 * e precisam existir já no `migrate`. Idempotente: em ambientes onde o seeder
 * já rodou, só insere os módulos que ainda faltam.
 */
return new class extends Migration
{
    /**
     * @var array<int, array{name: string, slug: string, description: string, route_prefix: string}>
     */
    private array $modules = [
        ['name' => 'Cadastros', 'slug' => 'registrations', 'description' => 'Gerenciamento de cadastros', 'route_prefix' => 'registrations'],
        ['name' => 'Vendas', 'slug' => 'sales', 'description' => 'Gerenciamento de vendas', 'route_prefix' => 'sales'],
        ['name' => 'Serviços', 'slug' => 'services', 'description' => 'Gerenciamento de serviços', 'route_prefix' => 'services'],
        ['name' => 'Produtos', 'slug' => 'products', 'description' => 'Gerenciamento de produtos', 'route_prefix' => 'products'],
        ['name' => 'Financeiro', 'slug' => 'finance', 'description' => 'Gerenciamento financeiro', 'route_prefix' => 'financial'],
        ['name' => 'Documentações', 'slug' => 'documents', 'description' => 'Criação de propostas', 'route_prefix' => 'documents'],
        ['name' => 'Configurações', 'slug' => 'settings', 'description' => 'Configurações do sistema', 'route_prefix' => 'settings'],
        ['name' => 'Drive', 'slug' => 'drive', 'description' => 'Gerenciamento de arquivos', 'route_prefix' => 'drive'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $existing = DB::table('modules')
            ->whereIn('slug', array_column($this->modules, 'slug'))
            ->pluck('slug')
            ->all();

        $now = now();

        $rows = collect($this->modules)
            ->reject(fn (array $module) => in_array($module['slug'], $existing, true))
            ->map(fn (array $module) => [
                ...$module,
                'icon' => '',
                'is_core' => true,
                'requires_modules' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($rows !== []) {
            DB::table('modules')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('modules')->whereIn('slug', array_column($this->modules, 'slug'))->delete();
    }
};
