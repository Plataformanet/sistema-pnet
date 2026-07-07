<?php

use App\Enums\TenantProvisioningStatus;
use App\Http\Controllers\TenantRegistrationController;
use App\Jobs\SeedTenantDatabase;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
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
        'name' => 'Acme',
        'domain' => 'acme.localhost',
        'plan_id' => 1,
        'userName' => 'Admin Acme',
        'email' => 'admin@acme.com',
        'password' => 'password',
    ], $overrides);
}

test('create tenant', function () {
    $tenant = makeTenant();

    expect($tenant)->not->toBeNull();
    expect($tenant->name)->toBe('Tenant de teste');
    expect($tenant->is_active)->toBeTrue();
});

test('create tenant with domain', function () {
    $tenant = makeTenant(['name' => 'tenant1', 'is_active' => true]);

    $tenant->domains()->create([
        'domain' => 'tenant1.localhost',
    ]);

    expect($tenant->domains()->first()->domain)->toBe('tenant1.localhost');
    expect($tenant->is_active)->toBeTrue();
});

test('store provisions a tenant with domain, modules and an admin user', function () {
    $tenant = app(TenantService::class)->store(tenantPayload());
    TenantRegistry::add($tenant);

    $this->assertDatabaseHas('tenants', [
        'id' => $tenant->id,
        'name' => 'Acme',
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

test('store marks the tenant as provisioned and clears the transient seed payload', function () {
    $tenant = app(TenantService::class)->store(tenantPayload([
        'domain' => 'provisioned.localhost',
        'email' => 'provisioned@acme.com',
    ]));
    TenantRegistry::add($tenant);

    $fresh = $tenant->fresh();

    expect($fresh->isProvisioned())->toBeTrue()
        ->and($fresh->seed)->toBeNull();
});

test('hasModule reflects the tenant active modules', function () {
    $tenant = app(TenantService::class)->store(tenantPayload());
    TenantRegistry::add($tenant);

    $result = $tenant->hasModule(['registrations', 'nonexistent']);

    expect($result['registrations'])->toBeTrue()
        ->and($result['nonexistent'])->toBeFalse();
});

test('store does not persist the tenant when the plan does not exist', function () {
    $threw = false;
    try {
        app(TenantService::class)->store(tenantPayload([
            'plan_id' => 999,
            'name' => 'Sem Plano',
            'domain' => 'semplano.localhost',
        ]));
    } catch (Throwable $e) {
        $threw = true;
    }

    expect($threw)->toBeTrue();

    $this->assertDatabaseMissing('tenants', ['name' => 'Sem Plano']);
    $this->assertDatabaseMissing('domains', ['domain' => 'semplano.localhost']);
});

test('store rolls back the tenant when the domain is already taken', function () {
    $service = app(TenantService::class);

    $first = $service->store(tenantPayload(['domain' => 'duplicate.localhost']));
    TenantRegistry::add($first);

    $threw = false;
    try {
        $service->store(tenantPayload([
            'name' => 'Segundo',
            'domain' => 'duplicate.localhost',
            'email' => 'segundo@acme.com',
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
    $tenant = makeTenant(['plan_id' => 2]);
    $module = Module::firstOrFail();

    expect(fn () => app(TenantService::class)->canActivateModule($tenant, $module))
        ->toThrow(Exception::class, 'Módulo não disponível no plano atual');
});

test('canActivateModule throws when the tenant is blocked', function () {
    $tenant = makeTenant(['plan_id' => 1, 'is_active' => false]);
    $module = Module::where('slug', 'registrations')->firstOrFail();

    expect(fn () => app(TenantService::class)->canActivateModule($tenant, $module))
        ->toThrow(Exception::class, 'Tenant bloqueado');
});

test('status endpoint reports a provisioned tenant as ready', function () {
    $tenant = makeTenant();
    $tenant->update(['provisioning_status' => TenantProvisioningStatus::READY->value]);

    $response = app(TenantRegistrationController::class)->status($tenant->id);

    expect($response->getData(true))->toMatchArray([
        'status' => 'ready',
        'ready' => true,
    ]);
});

test('status endpoint treats a missing tenant as failed', function () {
    $response = app(TenantRegistrationController::class)->status('inexistente');

    expect($response->getData(true))->toMatchArray([
        'status' => 'failed',
        'ready' => false,
    ]);
});

test('seeder failed() cleans up the half-provisioned tenant', function () {
    // Provisiona um tenant real (banco criado/migrado/semeado).
    $tenant = app(TenantService::class)->store(tenantPayload([
        'domain' => 'falha.localhost',
        'email' => 'falha@acme.com',
    ]));

    // Simula falha no seeder: deve marcar failed e remover o tenant (o cascade
    // de domains + o DeleteDatabase derrubam domínio e banco). Não registramos
    // no TenantRegistry porque o próprio failed() faz a limpeza.
    (new SeedTenantDatabase($tenant))->failed(new Exception('boom'));

    expect(Tenant::find($tenant->id))->toBeNull();
    $this->assertDatabaseMissing('domains', ['domain' => 'falha.localhost']);
});
