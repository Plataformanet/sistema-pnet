<?php

use App\Enums\FinancialCategoryEnum;
use App\Models\FinancialCategory;
use App\Models\FinancialSubcategory;
use App\Services\FinancialCategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    // Cria o tenant (e migra o banco dele). A limpeza do banco do tenant é
    // feita automaticamente pelo afterEach global em tests/Pest.php.
    $this->tenant = createTenant(['name' => 'Acme']);
});

test('creates financial category in tenant context', function () {
    $data = [
        'name' => 'Categoria de teste',
        'type' => FinancialCategoryEnum::EXPENSE,
    ];

    $category = app(FinancialCategoryService::class)->create($data, $this->tenant);

    // Dentro do run() a conexão padrão é a do tenant, então o assert bate no banco certo.
    $this->tenant->run(function () use ($category) {
        $this->assertDatabaseHas('financial_categories', [
            'id'   => $category->id,
            'name' => 'Categoria de teste',
            'type' => FinancialCategoryEnum::EXPENSE->value,
        ]);
    });
});

test('does not allow duplicate category with same name and type', function () {
    $data = [
        'name' => 'Categoria repetida',
        'type' => FinancialCategoryEnum::EXPENSE,
    ];

    $service = app(FinancialCategoryService::class);
    $service->create($data, $this->tenant);

    $service->create($data, $this->tenant);
})->throws(ValidationException::class);

test('allows categories with same name but different type', function () {
    $service = app(FinancialCategoryService::class);

    $expense = $service->create([
        'name' => 'Serviços',
        'type' => FinancialCategoryEnum::EXPENSE,
    ], $this->tenant);

    $income = $service->create([
        'name' => 'Serviços',
        'type' => FinancialCategoryEnum::INCOME,
    ], $this->tenant);

    expect($expense->id)->not->toBe($income->id);

    $this->tenant->run(function () {
        $this->assertDatabaseCount('financial_categories', 2);
    });
});

test('restores a soft-deleted category instead of creating a duplicate', function () {
    $service = app(FinancialCategoryService::class);

    $original = $service->create([
        'name' => 'Aluguel',
        'type' => FinancialCategoryEnum::EXPENSE,
    ], $this->tenant);

    $this->tenant->run(fn() => $original->delete());

    $restored = $service->create([
        'name'         => 'Aluguel',
        'type'         => FinancialCategoryEnum::EXPENSE,
        'observations' => 'Reaproveitada',
    ], $this->tenant);

    expect($restored->id)->toBe($original->id)
        ->and($restored->trashed())->toBeFalse()
        ->and($restored->observations)->toBe('Reaproveitada');

    $this->tenant->run(function () {
        $this->assertDatabaseCount('financial_categories', 1);
    });
});

test('updates a financial category', function () {
    $service = app(FinancialCategoryService::class);

    $category = $service->create([
        'name' => 'Categoria original',
        'type' => FinancialCategoryEnum::EXPENSE,
    ], $this->tenant);

    $result = $service->update($category->id, [
        'name'   => 'Categoria atualizada',
        'active' => false,
    ], $this->tenant);

    expect($result)->toBeTrue();

    $this->tenant->run(function () use ($category) {
        $this->assertDatabaseHas('financial_categories', [
            'id'     => $category->id,
            'name'   => 'Categoria atualizada',
            'active' => false,
        ]);
    });
});

test('deletes a category and its subcategories', function () {
    $service = app(FinancialCategoryService::class);

    $category = $service->create([
        'name' => 'Categoria com filhos',
        'type' => FinancialCategoryEnum::EXPENSE,
    ], $this->tenant);

    $this->tenant->run(function () use ($category) {
        $category->subcategories()->create(['name' => 'Subcategoria A']);
        $category->subcategories()->create(['name' => 'Subcategoria B']);
    });

    $result = $service->delete($category->id, $this->tenant);

    expect($result)->toBeTrue();

    $this->tenant->run(function () use ($category) {
        expect(FinancialCategory::find($category->id))->toBeNull()
            ->and(FinancialSubcategory::where('financial_category_id', $category->id)->count())->toBe(0);

        $this->assertSoftDeleted('financial_categories', ['id' => $category->id]);
        $this->assertDatabaseCount('financial_subcategories', 2);
    });
});

test('findById throws when the category does not exist', function () {
    app(FinancialCategoryService::class)->findById('999', $this->tenant);
})->throws(ModelNotFoundException::class);

test('findAll returns all categories in the tenant', function () {
    $service = app(FinancialCategoryService::class);

    $service->create(['name' => 'Cat 1', 'type' => FinancialCategoryEnum::EXPENSE], $this->tenant);
    $service->create(['name' => 'Cat 2', 'type' => FinancialCategoryEnum::INCOME], $this->tenant);

    expect($service->findAll($this->tenant))->toHaveCount(2);
});

test('filters categories by type for payable and receivable', function () {
    $service = app(FinancialCategoryService::class);

    $service->create(['name' => 'Despesa 1', 'type' => FinancialCategoryEnum::EXPENSE], $this->tenant);
    $service->create(['name' => 'Despesa 2', 'type' => FinancialCategoryEnum::EXPENSE], $this->tenant);
    $service->create(['name' => 'Receita 1', 'type' => FinancialCategoryEnum::INCOME], $this->tenant);

    $payable = $service->findCategoryAccountsPayable($this->tenant);
    $receivable = $service->findCategoryAccountsReceivable($this->tenant);

    expect($payable)->toHaveCount(2)
        ->and($receivable)->toHaveCount(1)
        ->and($receivable->first()->name)->toBe('Receita 1');
});
