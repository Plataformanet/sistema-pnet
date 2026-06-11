<?php

use App\Models\Tenant;
use Illuminate\Support\Str;
use Tests\Support\TenantRegistry;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->afterEach(function () {
        // Reverte a transação do tenant compartilhado e remove tenants reais.
        TenantRegistry::cleanup();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Tenant compartilhado e rápido: criado e migrado uma vez por suíte, com a
 * tenancy inicializada e o teste isolado numa transação (revertida no
 * afterEach). Use para testar recursos dentro do tenant.
 */
function sharedTenant(): Tenant
{
    return TenantRegistry::beginShared();
}

/**
 * Cria um tenant real (novo banco criado e migrado) e o registra para remoção
 * no afterEach. Use para testar o provisionamento de tenants em si — é lento
 * por natureza, pois exercita a criação do banco do tenant.
 *
 * @param  array<string, mixed>  $attributes
 */
function createTenant(array $attributes = []): Tenant
{
    $tenant = Tenant::create(array_merge([
        'name' => 'Tenant de teste',
        'is_active' => true,
    ], $attributes));

    TenantRegistry::add($tenant);

    return $tenant;
}

/**
 * Cria apenas a linha central do tenant, SEM disparar a criação/migração do
 * banco do tenant (suprime os eventos do model). Como não há DDL, a transação
 * central do teste reverte tudo no fim — sem necessidade de limpeza explícita.
 *
 * Use para testes que só tocam tabelas centrais (plano, módulos, domínios) e
 * não precisam do banco do tenant.
 *
 * @param  array<string, mixed>  $attributes
 */
function makeTenant(array $attributes = []): Tenant
{
    return Tenant::withoutEvents(fn () => Tenant::create(array_merge([
        'id' => (string) Str::uuid(),
        'name' => 'Tenant de teste',
        'is_active' => true,
    ], $attributes)));
}
