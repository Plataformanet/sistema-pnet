<?php

use App\Models\Tenant;
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
        // Dropa os bancos físicos dos tenants criados durante o teste.
        foreach (TenantRegistry::flush() as $tenant) {
            tenancy()->end();
            $tenant->delete();
        }
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
 * Cria um tenant para o teste — dispara a criação e migração do banco do
 * tenant — e o registra para limpeza automática no afterEach.
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
