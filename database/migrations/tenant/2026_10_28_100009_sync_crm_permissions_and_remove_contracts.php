<?php

use App\Enums\RolesEnum;
use App\Services\TenantPermissionService;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Leva ao tenant as permissões `crm.deals.view` e `crm.pipelines.view`
 * conforme o plano (RN-CORE-06) e remove `documents.contracts.*`, tirado do
 * catálogo. Exceção da V-12: quando o plano tem o módulo CRM, as duas
 * permissões também vão para o cargo Administrador. Idempotente.
 *
 * Depende da migration central `seed_crm_module_and_permissions`.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $crmPermissions = ['crm.deals.view', 'crm.pipelines.view'];

    /**
     * @var array<int, string>
     */
    private array $removedPermissions = [
        'documents.contracts.view',
        'documents.contracts.edit',
        'documents.contracts.create',
        'documents.contracts.delete',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        Permission::whereIn('name', $this->removedPermissions)->delete();

        $tenantPermissionService = app(TenantPermissionService::class);
        $tenantPermissionService->apply($tenantPermissionService->forTenant(tenant()));

        $granted = Permission::whereIn('name', $this->crmPermissions)->pluck('name')->all();

        if ($granted !== []) {
            Role::where('name', RolesEnum::ADMIN->label())->first()?->givePermissionTo($granted);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * As permissões `documents.contracts.*` voltam pelo `tenants:sync-permissions`
     * depois de reverter a migration central.
     */
    public function down(): void
    {
        Permission::whereIn('name', $this->crmPermissions)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
