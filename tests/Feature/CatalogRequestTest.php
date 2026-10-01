<?php

use App\Http\Requests\StoreBankRequest;
use App\Http\Requests\StoreBillableServiceRequest;
use App\Http\Requests\StoreCostTypeRequest;
use App\Http\Requests\StoreNotaryRequest;
use App\Http\Requests\StorePropertyTypeRequest;
use App\Http\Requests\UpdateBankRequest;
use App\Models\Bank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

/**
 * Valida o Form Request de cadastro, simulando o `{id}` da rota no update.
 *
 * @param  class-string<FormRequest>  $class
 * @param  array<string, mixed>  $data
 * @return array<string, array<int, string>>
 */
function catalogRequestErrors(string $class, array $data, ?int $routeId = null): array
{
    $request = $class::create('/', 'POST', $data);
    $request->setContainer(app())->setRedirector(app(Redirector::class));

    if ($routeId !== null) {
        $route = new Route('PUT', 'documents/catalog/{id}', fn () => null);
        $route->bind($request);
        $route->setParameter('id', (string) $routeId);
        $request->setRouteResolver(fn () => $route);
    }

    try {
        $request->validateResolved();
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    return [];
}

test('o nome é normalizado com trim e colapso de espaços', function () {
    $request = formRequest(StoreBankRequest::class, ['name' => "  Banco   do \t Nordeste  "]);

    expect($request->validated('name'))->toBe('Banco do Nordeste');
});

test('nome duplicado de registro ativo informa que já está cadastrado', function () {
    $errors = catalogRequestErrors(StoreBankRequest::class, ['name' => 'Bradesco']);

    expect($errors['name'])->toBe(['Este banco já está cadastrado.']);
});

test('nome de registro excluído sugere restaurar em vez de duplicar', function () {
    $this->tenant->run(fn () => Bank::factory()->create(['name' => 'Banco Antigo'])->delete());

    $errors = catalogRequestErrors(StoreBankRequest::class, ['name' => 'Banco Antigo']);

    expect($errors['name'])->toBe(['Já existe um cadastro excluído com este nome — restaure-o.']);
});

test('o update ignora o próprio registro na unicidade', function () {
    $bank = $this->tenant->run(fn () => Bank::where('name', 'Bradesco')->firstOrFail());

    expect(catalogRequestErrors(UpdateBankRequest::class, ['name' => 'Bradesco'], $bank->id))->toBe([]);
});

test('o cartório grava o CEP só com dígitos e exige UF válida', function () {
    $payload = [
        'name' => 'Cartório de Registro',
        'zip_code' => '01001-000',
        'street' => 'Praça da Sé',
        'number' => '100',
        'neighborhood' => 'Sé',
        'city' => 'São Paulo',
        'state' => 'SP',
    ];

    expect(formRequest(StoreNotaryRequest::class, $payload)->validated('zip_code'))->toBe('01001000');

    $errors = catalogRequestErrors(StoreNotaryRequest::class, array_merge($payload, ['state' => 'XX', 'name' => 'AB']));

    expect($errors)->toHaveKeys(['state', 'name']);
});

test('o tipo de custo só aceita recibo de assessoria ou motoboy', function () {
    $payload = ['name' => 'Novo Custo', 'requires_notary' => false];

    expect(formRequest(StoreCostTypeRequest::class, array_merge($payload, ['receipt_type' => 'advisory']))->validated('receipt_type'))->toBe('advisory')
        ->and(formRequest(StoreCostTypeRequest::class, array_merge($payload, ['receipt_type' => '']))->validated('receipt_type'))->toBeNull()
        ->and(catalogRequestErrors(StoreCostTypeRequest::class, array_merge($payload, ['receipt_type' => 'general'])))->toHaveKey('receipt_type');
});

test('o tipo de imóvel exige nome com no mínimo 3 caracteres', function () {
    $errors = catalogRequestErrors(StorePropertyTypeRequest::class, [
        'name' => 'AP',
        'shows_number' => true,
        'shows_complement' => true,
        'requires_development' => false,
        'shows_unit' => false,
        'shows_block' => false,
    ]);

    expect($errors['name'])->toBe(['O nome do tipo de imóvel deve ter no mínimo 3 caracteres.']);
});

test('o valor do serviço é aceito só em centavos inteiros', function () {
    $payload = ['name' => 'Assessoria', 'description' => 'Assessoria completa', 'generates_receipt' => true];

    expect(formRequest(StoreBillableServiceRequest::class, array_merge($payload, ['price' => 150000]))->validated('price'))->toBe(150000)
        ->and(catalogRequestErrors(StoreBillableServiceRequest::class, array_merge($payload, ['price' => '1.500,00'])))->toHaveKey('price');
});
