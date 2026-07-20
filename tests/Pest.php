<?php

use App\Enums\ContactTypeEnum;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\BankAccount;
use App\Models\FinancialCategory;
use App\Models\FinancialContact;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
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

/**
 * Cria um lançamento financeiro real (conta a pagar ou a receber) para o contato,
 * vinculado ao papel informado. É o que bloqueia a exclusão do papel nos cadastros.
 *
 * @param  class-string<AccountPayable|AccountReceivable>  $accountClass
 */
function createFinancialEntry(Tenant $tenant, int $contactId, ContactTypeEnum $type, string $accountClass): void
{
    $tenant->run(function () use ($contactId, $type, $accountClass) {
        $financialContact = FinancialContact::create([
            'contact_id' => $contactId,
            'type' => $type->value,
        ]);

        $category = FinancialCategory::create([
            'name' => 'Categoria Teste',
            'type' => $accountClass === AccountReceivable::class ? 'receita' : 'despesa',
        ]);

        $bankAccount = BankAccount::create([
            'name' => 'Conta Principal',
            'bank' => 'Banco Teste',
            'agency' => '0001',
            'account_number' => '123456',
            'account_type' => 'corrente',
            'initial_balance' => 0,
            'current_balance' => 0,
            'main_account' => 1,
        ]);

        $accountClass::create([
            'financial_category_id' => $category->id,
            'bank_account_id' => $bankAccount->id,
            'financial_contact_id' => $financialContact->id,
            'description' => 'Lançamento de teste',
            'total' => 10000,
            'payment_method' => 'pix',
            'payment_condition' => '1',
            'total_installments' => 1,
            'bank_account_out' => 1,
        ]);
    });
}

/**
 * Constrói uma instância de FormRequest já resolvida e validada, sem precisar de
 * rota HTTP. Útil para testar services cujos métodos recebem um FormRequest.
 *
 * @template T of \Illuminate\Foundation\Http\FormRequest
 *
 * @param  class-string<T>  $class
 * @param  array<string, mixed>  $data
 * @param  array<string, mixed>  $files
 * @return T
 */
function formRequest(string $class, array $data = [], array $files = []): FormRequest
{
    $request = $class::create('/', 'POST', $data, [], $files);
    $request->setContainer(app());
    $request->validateResolved();

    return $request;
}
