<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permissões dos Protocolos de Registro e dos Relatórios, no módulo
 * Documentações. Idempotente: insere só as que ainda não existem.
 */
return new class extends Migration
{
    /**
     * @var array<int, array{name: string, display_name: string}>
     */
    private array $permissions = [
        ['name' => 'documents.registry_protocols.view', 'display_name' => 'Protocolos de Registro (Visualizar)'],
        ['name' => 'documents.registry_protocols.edit', 'display_name' => 'Protocolos de Registro (Editar)'],
        ['name' => 'documents.registry_protocols.create', 'display_name' => 'Protocolos de Registro (Criar)'],
        ['name' => 'documents.registry_protocols.delete', 'display_name' => 'Protocolos de Registro (Excluir)'],
        ['name' => 'documents.reports.view', 'display_name' => 'Relatórios (Visualizar)'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $moduleId = DB::table('modules')->where('slug', 'documents')->value('id');

        if ($moduleId === null) {
            return;
        }

        $existing = DB::table('permissions')
            ->whereIn('name', array_column($this->permissions, 'name'))
            ->pluck('name')
            ->all();

        $now = now();

        $rows = collect($this->permissions)
            ->reject(fn (array $permission) => in_array($permission['name'], $existing, true))
            ->map(fn (array $permission) => [
                'module_id' => $moduleId,
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($rows !== []) {
            DB::table('permissions')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->whereIn('name', array_column($this->permissions, 'name'))->delete();
    }
};
