<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * RN-CRM-02: cria o módulo CRM (core) e as permissões `crm.deals.view` e
 * `crm.pipelines.view`, que só controlam o menu: o CRM continua sem backend
 * (P-14, exceção da V-15). Também remove do catálogo `documents.contracts.*`,
 * que não tem tela nem rota.
 *
 * O módulo entra nos planos que já têm algum módulo core e nos tenants desses
 * planos, para o CRM seguir visível como hoje. Idempotente: só insere o que
 * falta.
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $module = [
        'name' => 'CRM',
        'slug' => 'crm',
        'description' => 'Funil de vendas',
        'route_prefix' => 'crm',
    ];

    /**
     * @var array<int, array{name: string, display_name: string}>
     */
    private array $permissions = [
        ['name' => 'crm.deals.view', 'display_name' => 'Oportunidades (Visualizar)'],
        ['name' => 'crm.pipelines.view', 'display_name' => 'Funis (Visualizar)'],
    ];

    /**
     * @var array<int, array{name: string, display_name: string}>
     */
    private array $removedPermissions = [
        ['name' => 'documents.contracts.view', 'display_name' => 'Contratos (Visualizar)'],
        ['name' => 'documents.contracts.edit', 'display_name' => 'Contratos (Editar)'],
        ['name' => 'documents.contracts.create', 'display_name' => 'Contratos (Criar)'],
        ['name' => 'documents.contracts.delete', 'display_name' => 'Contratos (Excluir)'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->whereIn('name', array_column($this->removedPermissions, 'name'))->delete();

        $moduleId = DB::table('modules')->where('slug', $this->module['slug'])->value('id');

        if ($moduleId === null) {
            $moduleId = DB::table('modules')->insertGetId([
                ...$this->module,
                'icon' => '',
                'is_core' => true,
                'requires_modules' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->attachToPlansAndTenants($moduleId, $now);

        $existing = DB::table('permissions')
            ->whereIn('name', array_column($this->permissions, 'name'))
            ->pluck('name')
            ->all();

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
        $moduleId = DB::table('modules')->where('slug', $this->module['slug'])->value('id');

        DB::table('permissions')->whereIn('name', array_column($this->permissions, 'name'))->delete();

        if ($moduleId !== null) {
            DB::table('tenant_modules')->where('module_id', $moduleId)->delete();
            DB::table('plan_modules')->where('module_id', $moduleId)->delete();
            DB::table('modules')->where('id', $moduleId)->delete();
        }

        $existing = DB::table('permissions')
            ->whereIn('name', array_column($this->removedPermissions, 'name'))
            ->pluck('name')
            ->all();

        $documentsId = DB::table('modules')->where('slug', 'documents')->value('id');
        $now = now();

        $rows = collect($this->removedPermissions)
            ->reject(fn (array $permission) => in_array($permission['name'], $existing, true))
            ->map(fn (array $permission) => [
                'module_id' => $documentsId,
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($documentsId !== null && $rows !== []) {
            DB::table('permissions')->insert($rows);
        }
    }

    /**
     * Liga o módulo aos planos que já incluem módulos core e aos tenants
     * desses planos que ainda não o têm.
     */
    private function attachToPlansAndTenants(int $moduleId, Carbon $now): void
    {
        $coreModuleIds = DB::table('modules')->where('is_core', true)->where('id', '!=', $moduleId)->pluck('id');

        $planIds = DB::table('plan_modules')->whereIn('module_id', $coreModuleIds)->distinct()->pluck('plan_id');

        $linkedPlanIds = DB::table('plan_modules')->where('module_id', $moduleId)->pluck('plan_id');

        $rows = $planIds->diff($linkedPlanIds)->map(fn (int $planId) => [
            'plan_id' => $planId,
            'module_id' => $moduleId,
            'is_included' => true,
            'additional_price' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all();

        if ($rows !== []) {
            DB::table('plan_modules')->insert($rows);
        }

        $linkedTenantIds = DB::table('tenant_modules')->where('module_id', $moduleId)->pluck('tenant_id');

        $tenantRows = DB::table('tenants')
            ->whereIn('plan_id', DB::table('plan_modules')->where('module_id', $moduleId)->pluck('plan_id'))
            ->whereNotIn('id', $linkedTenantIds)
            ->pluck('id')
            ->map(fn (string $tenantId) => [
                'tenant_id' => $tenantId,
                'module_id' => $moduleId,
                'is_active' => true,
                'activated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($tenantRows !== []) {
            DB::table('tenant_modules')->insert($tenantRows);
        }
    }
};
