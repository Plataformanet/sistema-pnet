<?php

use App\Enums\RolesEnum;
use App\Models\Module;
use App\Models\Tenant;
use App\Services\TenantPermissionService;
use App\Services\TenantService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\TenantRegistry;

/**
 * Provisiona um tenant real no plano básico (todos os módulos core).
 */
function provisionTenantForPermissions(string $slug): Tenant
{
    $tenant = app(TenantService::class)->store([
        'name' => "Tenant {$slug}",
        'domain' => "{$slug}.localhost",
        'plan_id' => 1,
        'userName' => 'Admin',
        'email' => "admin@{$slug}.com",
        'password' => 'password',
    ]);

    TenantRegistry::add($tenant);

    return $tenant;
}

/**
 * Apaga permissões do banco do tenant, simulando um tenant desatualizado.
 */
function removeTenantPermissions(Tenant $tenant, string $prefix): void
{
    $tenant->run(function () use ($prefix) {
        Permission::where('name', 'like', "{$prefix}%")->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    });
}

test('forTenant returns only the permissions of the tenant active modules', function () {
    $tenant = provisionTenantForPermissions('perm-active');
    $drive = Module::where('slug', 'drive')->firstOrFail();

    $tenant->modules()->updateExistingPivot($drive->id, ['is_active' => false]);

    $names = array_column(app(TenantPermissionService::class)->forTenant($tenant), 'name');

    expect($names)->toContain('registrations.clients.view')
        ->and($names)->not->toContain('drive.drives.view')
        ->and($names)->toBe(array_values(array_unique($names)));
});

test('sync restores missing plan permissions and grants them to the administrator', function () {
    $tenant = provisionTenantForPermissions('perm-sync');
    removeTenantPermissions($tenant, 'registrations.clients.');

    app(TenantPermissionService::class)->sync($tenant);

    $tenant->run(function () {
        $admin = Role::where('name', 'Administrador')->firstOrFail();

        expect(Permission::where('name', 'registrations.clients.view')->exists())->toBeTrue()
            ->and($admin->hasPermissionTo('registrations.clients.view'))->toBeTrue()
            ->and($admin->permissions()->count())->toBe(Permission::count());
    });
});

test('sync does not create permissions of modules that are not active for the tenant', function () {
    $tenant = provisionTenantForPermissions('perm-inactive');
    $drive = Module::where('slug', 'drive')->firstOrFail();

    $tenant->modules()->updateExistingPivot($drive->id, ['is_active' => false]);
    removeTenantPermissions($tenant, 'drive.');

    app(TenantPermissionService::class)->sync($tenant);

    $tenant->run(function () {
        expect(Permission::where('name', 'like', 'drive.%')->exists())->toBeFalse();
    });
});

test('provisioning grants the default permissions of the operational roles', function () {
    $tenant = provisionTenantForPermissions('perm-defaults');

    $tenant->run(function () {
        $documentsPermissions = Permission::where('name', 'like', 'documents.%')->pluck('name')->sort()->values()->all();
        $analyst = Role::findByName(RolesEnum::ANALYST->label(), 'web');
        $partner = Role::findByName(RolesEnum::PARTNER->label(), 'web');

        expect($documentsPermissions)->not->toBeEmpty()
            ->and($analyst->permissions->pluck('name')->sort()->values()->all())->toBe($documentsPermissions)
            ->and($partner->hasPermissionTo('documents.proposals.create'))->toBeTrue()
            ->and(Role::findByName(RolesEnum::CLIENT->label(), 'web')->hasPermissionTo('documents.proposals.view'))->toBeTrue();
    });
});

test('sync does not restore role defaults removed by the administrator', function () {
    $tenant = provisionTenantForPermissions('perm-keep-roles');

    $tenant->run(fn () => Role::findByName(RolesEnum::PARTNER->label(), 'web')
        ->revokePermissionTo('documents.proposals.create'));

    app(TenantPermissionService::class)->sync($tenant);

    $tenant->run(function () {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        expect(Role::findByName(RolesEnum::PARTNER->label(), 'web')->hasPermissionTo('documents.proposals.create'))->toBeFalse();
    });
});

test('sync command restores the permissions of the selected tenants', function () {
    $tenant = provisionTenantForPermissions('perm-command');
    removeTenantPermissions($tenant, 'settings.users.');

    $this->artisan('tenants:sync-permissions', ['--tenants' => [$tenant->id]])
        ->assertSuccessful();

    $tenant->run(function () {
        expect(Permission::where('name', 'settings.users.view')->exists())->toBeTrue();
    });
});
