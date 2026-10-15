<?php

use App\Enums\RolesEnum;
use App\Services\TenantPermissionService;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Leva as permissões dos Protocolos de Registro e dos Relatórios aos tenants
 * já provisionados, conforme o plano de cada um. O Analista tem acesso total
 * ao grupo "Documentações", então recebe as novas permissões aqui; nos
 * tenants novos isso vem de `TenantPermissionService::applyRoleDefaults()`.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'documents.registry_protocols.view',
        'documents.registry_protocols.edit',
        'documents.registry_protocols.create',
        'documents.registry_protocols.delete',
        'documents.reports.view',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        $tenantPermissionService = app(TenantPermissionService::class);
        $tenantPermissionService->apply($tenantPermissionService->forTenant(tenant()));

        Role::where('name', RolesEnum::ANALYST->label())
            ->first()
            ?->givePermissionTo(Permission::whereIn('name', $this->permissions)->pluck('name')->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', $this->permissions)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
