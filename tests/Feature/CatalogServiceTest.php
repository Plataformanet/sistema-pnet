<?php

use App\Enums\BrazilianState;
use App\Models\Bank;
use App\Models\BillableService;
use App\Models\ContractType;
use App\Models\CostType;
use App\Models\Development;
use App\Models\Notary;
use App\Models\PropertyType;
use App\Services\BankService;
use App\Services\BillableServiceService;
use App\Services\CatalogService;
use App\Services\ContractTypeService;
use App\Services\CostTypeService;
use App\Services\DevelopmentService;
use App\Services\NotaryService;
use App\Services\PropertyTypeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

dataset('catalogs', [
    'bancos' => [BankService::class, Bank::class, fn () => ['name' => 'Banco Catálogo']],
    'cartórios' => [NotaryService::class, Notary::class, fn () => [
        'name' => 'Cartório Catálogo',
        'zip_code' => '01001000',
        'street' => 'Praça da Sé',
        'number' => '100',
        'neighborhood' => 'Sé',
        'city' => 'São Paulo',
        'state' => BrazilianState::SP->value,
    ]],
    'tipos de contrato' => [ContractTypeService::class, ContractType::class, fn () => ['name' => 'Contrato Catálogo', 'requires_financing' => false]],
    'tipos de custo' => [CostTypeService::class, CostType::class, fn () => ['name' => 'Custo Catálogo', 'requires_notary' => true, 'receipt_type' => null]],
    'tipos de imóvel' => [PropertyTypeService::class, PropertyType::class, fn () => [
        'name' => 'Imóvel Catálogo',
        'shows_number' => true,
        'shows_complement' => true,
        'requires_development' => false,
        'shows_unit' => false,
        'shows_block' => false,
    ]],
    'empreendimentos' => [DevelopmentService::class, Development::class, fn () => ['name' => 'Empreendimento Catálogo']],
    'serviços cobráveis' => [BillableServiceService::class, BillableService::class, fn () => [
        'name' => 'Serviço Catálogo',
        'description' => 'Descrição do serviço',
        'price' => 150000,
        'generates_receipt' => true,
    ]],
]);

test('store e update gravam o cadastro', function (string $serviceClass, string $modelClass, Closure $payload) {
    /** @var CatalogService $service */
    $service = app($serviceClass);

    $record = $service->store($payload(), $this->tenant);
    $service->update(array_merge($payload(), ['name' => 'Nome Atualizado']), (string) $record->id, $this->tenant);

    $this->tenant->run(function () use ($modelClass, $record) {
        expect($modelClass::findOrFail($record->id)->name)->toBe('Nome Atualizado');
    });
})->with('catalogs');

test('excluir faz soft delete e restaurar devolve o registro à listagem', function (string $serviceClass, string $modelClass, Closure $payload) {
    /** @var CatalogService $service */
    $service = app($serviceClass);
    $record = $service->store($payload(), $this->tenant);

    $service->delete((string) $record->id, $this->tenant);

    $this->tenant->run(function () use ($modelClass, $record) {
        expect($modelClass::find($record->id))->toBeNull()
            ->and($modelClass::withTrashed()->find($record->id))->not->toBeNull();
    });

    $activeIds = collect($service->findAll(['search' => $payload()['name']], $this->tenant)->items())->pluck('id');
    expect($activeIds)->not->toContain($record->id);

    $withTrashedIds = collect($service->findAll(['search' => $payload()['name'], 'trashed' => true], $this->tenant)->items())->pluck('id');
    expect($withTrashedIds)->toContain($record->id);

    $service->restore((string) $record->id, $this->tenant);

    $this->tenant->run(fn () => expect($modelClass::find($record->id))->not->toBeNull());
})->with('catalogs');

test('restaurar um registro ativo falha', function () {
    $bank = app(BankService::class)->store(['name' => 'Banco Ativo'], $this->tenant);

    app(BankService::class)->restore((string) $bank->id, $this->tenant);
})->throws(ModelNotFoundException::class);

test('a listagem é paginada no servidor e filtra pela busca', function () {
    $this->tenant->run(fn () => Bank::factory()->count(25)->create());

    $page = app(BankService::class)->findAll([], $this->tenant);

    expect($page->perPage())->toBe(CatalogService::PER_PAGE)
        ->and($page->total())->toBeGreaterThanOrEqual(30)
        ->and(count($page->items()))->toBe(CatalogService::PER_PAGE);

    $filtered = app(BankService::class)->findAll(['search' => 'Bradesco'], $this->tenant);

    expect(collect($filtered->items())->pluck('name')->all())->toBe(['Bradesco']);
});

test('a listagem informa quantas vezes o cadastro está em uso', function () {
    $costType = $this->tenant->run(fn () => CostType::factory()->create(['name' => 'Centro em uso']));

    $row = collect(app(CostTypeService::class)->findAll(['search' => 'Centro em uso'], $this->tenant)->items())->first();

    expect($row)->toHaveKey('in_use_count')
        ->and($row['in_use_count'])->toBe(0)
        ->and($row['id'])->toBe($costType->id);
});

test('options não oferece excluídos, exceto o valor atual do registro em edição', function () {
    [$active, $trashed] = $this->tenant->run(function () {
        $active = Development::factory()->create();
        $trashed = Development::factory()->create();
        $trashed->delete();

        return [$active, $trashed];
    });

    $service = app(DevelopmentService::class);

    expect($service->options($this->tenant)->pluck('id'))->toContain($active->id)
        ->not->toContain($trashed->id)
        ->and($service->options($this->tenant, $trashed->id)->pluck('id'))->toContain($active->id, $trashed->id);
});

test('a carga inicial cria bancos, tipos de contrato, tipos de custo e etapas', function () {
    $this->tenant->run(function () {
        expect(Bank::where('name', 'Itaú')->exists())->toBeTrue()
            ->and(ContractType::findOrFail(4)->requires_financing)->toBeFalse()
            ->and(ContractType::findOrFail(1)->requires_financing)->toBeTrue()
            ->and(CostType::where('name', 'Assessoria')->firstOrFail()->generatesReceipt())->toBeTrue()
            ->and(CostType::where('name', 'Registro')->firstOrFail()->generatesReceipt())->toBeFalse();
    });
});
