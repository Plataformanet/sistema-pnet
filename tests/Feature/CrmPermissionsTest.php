<?php

use App\Enums\RolesEnum;
use App\Models\Module;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = sharedTenant();
    $this->central = config('tenancy.database.central_connection');
});

/**
 * Roda a migration de tenant que sincroniza as permissões do CRM no tenant atual.
 */
function runCrmTenantMigration(): void
{
    $migration = require database_path('migrations/tenant/2026_10_28_100009_sync_crm_permissions_and_remove_contracts.php');
    $migration->up();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

test('o catálogo central tem o módulo CRM core com as permissões de menu', function () {
    $module = Module::on($this->central)->where('slug', 'crm')->first();

    expect($module)->not->toBeNull()
        ->and($module->is_core)->toBeTrue()
        ->and($module->permissions()->pluck('name')->sort()->values()->all())
        ->toBe(['crm.deals.view', 'crm.pipelines.view']);
});

test('o módulo CRM está em todo plano que tem módulos core', function () {
    $crmId = Module::on($this->central)->where('slug', 'crm')->value('id');
    $plansWithCore = DB::connection($this->central)->table('plan_modules')->where('module_id', '!=', $crmId)->distinct()->pluck('plan_id');

    expect($plansWithCore)->not->toBeEmpty()
        ->and(DB::connection($this->central)->table('plan_modules')->where('module_id', $crmId)->pluck('plan_id')->sort()->values()->all())
        ->toBe($plansWithCore->sort()->values()->all());
});

test('o catálogo central não tem mais documents.contracts.*', function () {
    expect(DB::connection($this->central)->table('permissions')->where('name', 'like', 'documents.contracts.%')->exists())->toBeFalse();
});

test('o tenant provisionado com o plano completo recebe as permissões do CRM e nenhuma de contratos', function () {
    $this->tenant->run(function () {
        expect(Permission::where('name', 'like', 'crm.%')->pluck('name')->sort()->values()->all())
            ->toBe(['crm.deals.view', 'crm.pipelines.view'])
            ->and(Permission::where('name', 'like', 'documents.contracts.%')->exists())->toBeFalse();
    });
});

test('a migration do tenant atribui as permissões do CRM ao Administrador quando o plano tem o módulo (V-12)', function () {
    $crmId = Module::on($this->central)->where('slug', 'crm')->value('id');

    $this->tenant->run(function () {
        Permission::where('name', 'like', 'crm.%')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    });

    DB::connection($this->central)->table('tenant_modules')->insert([
        'tenant_id' => $this->tenant->getTenantKey(),
        'module_id' => $crmId,
        'is_active' => true,
        'activated_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->tenant->run(function () {
        runCrmTenantMigration();

        $admin = Role::findByName(RolesEnum::ADMIN->label());
        $analyst = Role::findByName(RolesEnum::ANALYST->label());

        expect($admin->hasPermissionTo('crm.deals.view'))->toBeTrue()
            ->and($admin->hasPermissionTo('crm.pipelines.view'))->toBeTrue()
            ->and($analyst->hasPermissionTo('crm.deals.view'))->toBeFalse();
    });
});

test('a migration do tenant não cria permissões do CRM quando o plano não tem o módulo', function () {
    $this->tenant->run(function () {
        Permission::where('name', 'like', 'crm.%')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        runCrmTenantMigration();

        expect(Permission::where('name', 'like', 'crm.%')->exists())->toBeFalse();
    });
});

test('as rotas do CRM exigem crm.deals.view', function (string $routeName) {
    expect(Route::getRoutes()->getByName($routeName)->gatherMiddleware())
        ->toContain('permission:crm.deals.view');
})->with(['tenant.crm.kanban', 'tenant.crm.list']);

test('sem a permissão o usuário não passa pelo middleware do CRM', function () {
    $user = userWithRole($this->tenant, RolesEnum::MANAGER);

    $this->tenant->run(function () use ($user) {
        expect($user->can('crm.deals.view'))->toBeFalse();
    });
});

test('com a permissão o usuário passa pelo middleware do CRM', function () {
    $user = userWithRole($this->tenant, RolesEnum::MANAGER, ['crm.deals.view']);

    $this->tenant->run(function () use ($user) {
        expect($user->can('crm.deals.view'))->toBeTrue();
    });
});
