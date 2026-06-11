<?php

use App\Models\Module;
use App\Models\User;
use App\Services\TenantService;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\TenantRegistry;

/**
 * Monta o payload aceito por TenantService::store, permitindo sobrescrever
 * qualquer campo por teste.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function tenantPayload(array $overrides = []): array
{
    return array_merge([
        'name'     => 'Acme',
        'domain'   => 'acme.localhost',
        'plan_id'  => 1,
        'userName' => 'Admin Acme',
        'email'    => 'admin@acme.com',
        'password' => 'password',
    ], $overrides);
}

test('create tenant', function () {
    $tenant = createTenant();

    expect($tenant)->not->toBeNull();
    expect($tenant->name)->toBe('Tenant de teste');
    expect($tenant->is_active)->toBeTrue();
});

test('create tenant with domain', function () {
    $tenant = createTenant(['name' => 'tenant1', 'is_active' => true]);

    $tenant->domains()->create([
        'domain' => 'tenant1.localhost',
    ]);

    expect($tenant->domains()->first()->domain)->toBe('tenant1.localhost');
    expect($tenant->is_active)->toBeTrue();
});

test('store provisions a tenant with domain, modules and an admin user', function () {
    $this->seed(DatabaseSeeder::class);

    $tenant = app(TenantService::class)->store(tenantPayload());
    TenantRegistry::add($tenant);

    $this->assertDatabaseHas('tenants', [
        'id'        => $tenant->id,
        'name'      => 'Acme',
        'is_active' => true,
    ]);
    $this->assertDatabaseHas('domains', ['domain' => 'acme.localhost', 'tenant_id' => $tenant->id]);
    expect($tenant->trial_ends_at)->not->toBeNull();

    $includedModulesCount = $tenant->plan->includedModules()->count();
    expect($tenant->modules()->count())->toBe($includedModulesCount)->toBeGreaterThan(0);

    $tenant->run(function () {
        expect(Role::count())->toBe(5)
            ->and(Permission::count())->toBeGreaterThan(0);

        $user = User::where('email', 'admin@acme.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('Admin Acme')
            ->and($user->hasRole('Administrador'))->toBeTrue()
            ->and($user->getAllPermissions()->count())->toBe(Permission::count());
    });
});

test('hasModule reflects the tenant active modules', function () {
    $this->seed(DatabaseSeeder::class);

    $tenant = app(TenantService::class)->store(tenantPayload());
    TenantRegistry::add($tenant);

    $result = $tenant->hasModule(['registrations', 'nonexistent']);

    expect($result['registrations'])->toBeTrue()
        ->and($result['nonexistent'])->toBeFalse();
});

test('store does not persist the tenant when the plan does not exist', function () {
    $this->seed(DatabaseSeeder::class);

    $threw = false;
    try {
        app(TenantService::class)->store(tenantPayload([
            'plan_id' => 999,
            'name'    => 'Sem Plano',
            'domain'  => 'semplano.localhost',
        ]));
    } catch (Throwable $e) {
        $threw = true;
    }

    expect($threw)->toBeTrue();

    $this->assertDatabaseMissing('tenants', ['name' => 'Sem Plano']);
    $this->assertDatabaseMissing('domains', ['domain' => 'semplano.localhost']);
});

test('store rolls back the tenant when the domain is already taken', function () {
    $this->seed(DatabaseSeeder::class);

    $service = app(TenantService::class);

    $first = $service->store(tenantPayload(['domain' => 'duplicate.localhost']));
    TenantRegistry::add($first);

    $threw = false;
    try {
        $service->store(tenantPayload([
            'name'   => 'Segundo',
            'domain' => 'duplicate.localhost',
            'email'  => 'segundo@acme.com',
        ]));
    } catch (Throwable $e) {
        $threw = true;
    }

    expect($threw)->toBeTrue();

    $this->assertDatabaseMissing('tenants', ['name' => 'Segundo']);
    $this->assertDatabaseHas('domains', ['domain' => 'duplicate.localhost', 'tenant_id' => $first->id]);
    $this->assertDatabaseCount('domains', 1);
});

test('canActivateModule throws when the module is not in the tenant plan', function () {
    $this->seed(DatabaseSeeder::class);

    $tenant = createTenant(['plan_id' => 2]);
    $module = Module::firstOrFail();

    expect(fn() => app(TenantService::class)->canActivateModule($tenant, $module))
        ->toThrow(Exception::class, 'Módulo não disponível no plano atual');
});

test('canActivateModule throws when the tenant is blocked', function () {
    $this->seed(DatabaseSeeder::class);

    $tenant = createTenant(['plan_id' => 1, 'is_active' => false]);
    $module = Module::where('slug', 'registrations')->firstOrFail();

    expect(fn() => app(TenantService::class)->canActivateModule($tenant, $module))
        ->toThrow(Exception::class, 'Tenant bloqueado');
});
