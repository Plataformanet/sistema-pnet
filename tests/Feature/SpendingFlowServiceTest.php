<?php

use App\Enums\AccountsEnum;
use App\Enums\TypeContactEnum;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\FinancialCategory;
use App\Models\FinancialSubcategory;
use App\Services\AccountPayableService;
use App\Services\SpendingFlowService;

beforeEach(function () {
    $this->tenant = sharedTenant();

    [$this->contact, $this->bankAccount] = $this->tenant->run(function () {
        $contact = Contact::create([
            'type' => TypeContactEnum::SUPPLIER->value,
            'name_corporatereason' => 'Fornecedor Gastos',
            'cpf_cnpj' => '12345678000190',
            'email' => 'gastos@teste.com',
            'phone' => '1133334444',
            'cell_phone' => '11999998888',
        ]);

        $bankAccount = BankAccount::create([
            'name' => 'Conta Principal',
            'bank' => 'Banco Teste',
            'agency' => '0001',
            'account_number' => '111111',
            'account_type' => 'corrente',
            'initial_balance' => 0,
            'current_balance' => 0,
            'main_account' => 1,
        ]);

        return [$contact, $bankAccount];
    });
});

/**
 * Cria uma conta a pagar com as parcelas informadas (cada uma com value e due_date).
 *
 * @param  array<int, array{value: int, due_date: string}>  $installments
 */
function makeSpendingPayable(int $categoryId, ?int $subcategoryId, array $installments): void
{
    $total = collect($installments)->sum('value');

    app(AccountPayableService::class)->create([
        'financial_category_id' => $categoryId,
        'financial_subcategory_id' => $subcategoryId,
        'bank_account_id' => test()->bankAccount->id,
        'financial_contact_id' => test()->contact->id,
        'description' => 'Gasto',
        'total' => $total,
        'payment_method' => 'pix',
        'payment_condition' => 'a-vista',
        'total_installments' => count($installments),
        'bank_account_out' => 1,
        'value' => $total,
        'due_date' => $installments[0]['due_date'],
        'status' => AccountsEnum::OPEN->value,
        'installments' => $installments,
    ], test()->tenant);
}

/**
 * Retorna a linha do resultado correspondente à categoria informada.
 *
 * @return array<string, mixed>
 */
function spendingFlowEntry(array $result, int $categoryId): array
{
    return $result['categories']->first(fn ($entry) => $entry['category']['id'] === $categoryId);
}

test('categoria sem subcategorias soma seus proprios lancamentos por mes', function () {
    $category = $this->tenant->run(fn () => FinancialCategory::create([
        'name' => 'Despesas Gerais',
        'type' => 'despesa',
    ]));

    makeSpendingPayable($category->id, null, [
        ['value' => 10000, 'due_date' => '2026-01-15'],
        ['value' => 25000, 'due_date' => '2026-03-10'],
    ]);

    $result = app(SpendingFlowService::class)->calculateSpendingFlow($this->tenant, null, 2026);

    $entry = spendingFlowEntry($result, $category->id);

    expect($entry['has_subcategories'])->toBeFalse()
        ->and($entry['subcategories'])->toBe([])
        ->and($entry['months'][1])->toBe(10000)
        ->and($entry['months'][3])->toBe(25000)
        ->and($entry['months'][2])->toBe(0)
        ->and($entry['total'])->toBe(35000)
        ->and($result['totalsByMonth'][1])->toBe(10000)
        ->and($result['totalsByMonth'][3])->toBe(25000)
        ->and($result['grandTotal'])->toBe(35000);
});

test('categoria com subcategorias agrega as subcategorias e inclui lancamentos diretos sob sem subcategoria', function () {
    [$category, $subA, $subB] = $this->tenant->run(function () {
        $category = FinancialCategory::create(['name' => 'Despesas', 'type' => 'despesa']);
        $subA = FinancialSubcategory::create(['financial_category_id' => $category->id, 'name' => 'Sub A']);
        $subB = FinancialSubcategory::create(['financial_category_id' => $category->id, 'name' => 'Sub B']);

        return [$category, $subA, $subB];
    });

    makeSpendingPayable($category->id, $subA->id, [
        ['value' => 10000, 'due_date' => '2026-01-15'],
        ['value' => 5000, 'due_date' => '2026-02-20'],
    ]);
    makeSpendingPayable($category->id, $subB->id, [
        ['value' => 20000, 'due_date' => '2026-01-05'],
    ]);
    // Lancamento direto na categoria principal.
    makeSpendingPayable($category->id, null, [
        ['value' => 99000, 'due_date' => '2026-01-08'],
    ]);

    $result = app(SpendingFlowService::class)->calculateSpendingFlow($this->tenant, null, 2026);

    $entry = spendingFlowEntry($result, $category->id);

    expect($entry['has_subcategories'])->toBeTrue()
        ->and($entry['months'][1])->toBe(129000) // 10000 + 20000 + 99000
        ->and($entry['months'][2])->toBe(5000)
        ->and($entry['total'])->toBe(134000)
        ->and($entry['subcategories'])->toHaveCount(3);

    $subAData = collect($entry['subcategories'])->firstWhere('subcategory.id', $subA->id);

    expect($subAData['months'][1])->toBe(10000)
        ->and($subAData['months'][2])->toBe(5000)
        ->and($subAData['total'])->toBe(15000);

    $directData = collect($entry['subcategories'])->firstWhere('subcategory.id', null);

    expect($directData['subcategory']['name'])->toBe('Sem subcategoria')
        ->and($directData['months'][1])->toBe(99000)
        ->and($directData['total'])->toBe(99000)
        ->and($result['grandTotal'])->toBe(134000);
});

test('exclui parcelas de outro ano via filtro de due_date', function () {
    $category = $this->tenant->run(fn () => FinancialCategory::create([
        'name' => 'Despesas Anuais',
        'type' => 'despesa',
    ]));

    makeSpendingPayable($category->id, null, [
        ['value' => 40000, 'due_date' => '2026-06-12'],
        ['value' => 99000, 'due_date' => '2025-06-12'], // ano anterior, nao entra
    ]);

    $result = app(SpendingFlowService::class)->calculateSpendingFlow($this->tenant, null, 2026);

    $entry = spendingFlowEntry($result, $category->id);

    expect($entry['months'][6])->toBe(40000)
        ->and($entry['total'])->toBe(40000)
        ->and($result['grandTotal'])->toBe(40000);
});

test('filtra por categoria quando categoryId e informado', function () {
    [$categoryA, $categoryB] = $this->tenant->run(function () {
        return [
            FinancialCategory::create(['name' => 'Categoria A', 'type' => 'despesa']),
            FinancialCategory::create(['name' => 'Categoria B', 'type' => 'despesa']),
        ];
    });

    makeSpendingPayable($categoryA->id, null, [['value' => 10000, 'due_date' => '2026-04-10']]);
    makeSpendingPayable($categoryB->id, null, [['value' => 20000, 'due_date' => '2026-04-10']]);

    $result = app(SpendingFlowService::class)->calculateSpendingFlow($this->tenant, $categoryA->id, 2026);

    expect($result['categories'])->toHaveCount(1)
        ->and($result['categories']->first()['category']['id'])->toBe($categoryA->id)
        ->and($result['grandTotal'])->toBe(10000);
});

test('retorna category e subcategory apenas com id e name e calcula as medias', function () {
    [$category, $sub] = $this->tenant->run(function () {
        $category = FinancialCategory::create(['name' => 'Despesas Medias', 'type' => 'despesa']);
        $sub = FinancialSubcategory::create(['financial_category_id' => $category->id, 'name' => 'Sub Unica']);

        return [$category, $sub];
    });

    makeSpendingPayable($category->id, $sub->id, [['value' => 120000, 'due_date' => '2026-01-10']]);

    $result = app(SpendingFlowService::class)->calculateSpendingFlow($this->tenant, null, 2026);

    $entry = spendingFlowEntry($result, $category->id);

    expect(array_keys($entry['category']))->toEqualCanonicalizing(['id', 'name'])
        ->and(array_keys($entry['subcategories'][0]['subcategory']))->toEqualCanonicalizing(['id', 'name'])
        ->and($result['grandTotal'])->toBe(120000)
        ->and($result['monthlyAverage'])->toBe(120000 / 12)
        ->and($result['dailyAverage'])->toBe((120000 / 12) / 30);
});
