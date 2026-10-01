<?php

use App\Enums\ProposalStatus;
use App\Enums\RolesEnum;
use App\Http\Requests\StoreProposalCostItemRequest;
use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Models\Bank;
use App\Models\ContractType;
use App\Models\CostType;
use App\Models\Development;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->tenant = sharedTenant();

    $this->creator = userWithRole($this->tenant, RolesEnum::ADMIN);

    [$this->bank, $this->withFinancing, $this->withoutFinancing] = $this->tenant->run(fn () => [
        Bank::factory()->create(),
        ContractType::factory()->create(),
        ContractType::factory()->withoutFinancing()->create(),
    ]);
});

/**
 * @param  class-string<FormRequest>  $class
 * @param  array<string, mixed>  $data
 * @return array<string, array<int, string>>
 */
function proposalRequestErrors(string $class, array $data, ?int $routeId = null): array
{
    $request = $class::create('/', 'POST', $data);
    $request->setContainer(app())->setRedirector(app(Redirector::class));

    if ($routeId !== null) {
        $route = new Route('PUT', 'documents/proposals/{id}', fn () => null);
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

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function storeProposalData(array $overrides = []): array
{
    return array_merge([
        'creator_id' => test()->creator->id,
        'bank_id' => test()->bank->id,
        'contract_type_id' => test()->withFinancing->id,
        'amortization_table' => 'price',
        'payment_term' => 240,
        'property_condition' => 'used',
        'purchase_value' => 30_000_000,
        'down_payment_value' => 6_000_000,
        'applicants' => [['cpf' => '529.982.247-25', 'existing' => true]],
    ], $overrides);
}

test('contrato que exige financiamento obriga tabela e prazo', function () {
    $errors = proposalRequestErrors(StoreProposalRequest::class, storeProposalData([
        'amortization_table' => null,
        'payment_term' => null,
    ]));

    expect($errors)->toHaveKeys(['amortization_table', 'payment_term']);
});

test('contrato sem financiamento dispensa tabela e prazo', function () {
    $errors = proposalRequestErrors(StoreProposalRequest::class, storeProposalData([
        'contract_type_id' => $this->withoutFinancing->id,
        'amortization_table' => null,
        'payment_term' => null,
    ]));

    expect($errors)->toBe([]);
});

test('exige ao menos um proponente e CPFs válidos e distintos', function () {
    expect(proposalRequestErrors(StoreProposalRequest::class, storeProposalData(['applicants' => []])))->toHaveKey('applicants');

    $errors = proposalRequestErrors(StoreProposalRequest::class, storeProposalData(['applicants' => [
        ['cpf' => '52998224725', 'existing' => true],
        ['cpf' => '529.982.247-25', 'existing' => true],
        ['cpf' => '11111111111', 'existing' => true],
    ]]));

    expect($errors)->toHaveKeys(['applicants.0.cpf', 'applicants.2.cpf']);
});

test('proponente novo exige os dados cadastrais', function () {
    $errors = proposalRequestErrors(StoreProposalRequest::class, storeProposalData([
        'applicants' => [['cpf' => '52998224725', 'name' => 'AB']],
    ]));

    expect($errors)->toHaveKeys([
        'applicants.0.name',
        'applicants.0.email',
        'applicants.0.phone',
        'applicants.0.declared_income',
        'applicants.0.marital_status',
        'applicants.0.profession',
    ]);
});

test('valores monetários aceitam só centavos inteiros', function () {
    expect(proposalRequestErrors(StoreProposalRequest::class, storeProposalData(['purchase_value' => '300.000,00'])))
        ->toHaveKey('purchase_value');
});

test('o documento do vendedor é validado como CPF ou CNPJ conforme o tipo de pessoa', function () {
    $seller = ['name' => 'Construtora', 'email' => 'c@example.com'];

    $errors = proposalRequestErrors(StoreProposalRequest::class, storeProposalData(['sellers' => [
        array_merge($seller, ['person_type' => 'PJ', 'document' => '52998224725']),
        array_merge($seller, ['person_type' => 'PJ', 'document' => '11.222.333/0001-81']),
    ]]));

    expect($errors)->toHaveKey('sellers.0.document')
        ->not->toHaveKey('sellers.1.document');
});

test('tipo de imóvel de empreendimento exige o empreendimento', function () {
    [$propertyType, $development] = $this->tenant->run(fn () => [PropertyType::factory()->development()->create(), Development::factory()->create()]);

    expect(proposalRequestErrors(StoreProposalRequest::class, storeProposalData([
        'property' => ['property_type_id' => $propertyType->id],
    ])))->toHaveKey('property.development_id')
        ->and(proposalRequestErrors(StoreProposalRequest::class, storeProposalData([
            'property' => ['property_type_id' => $propertyType->id, 'development_id' => $development->id],
        ])))->toBe([]);
});

test('banco excluído não pode ser escolhido em proposta nova', function () {
    $this->tenant->run(fn () => $this->bank->delete());

    expect(proposalRequestErrors(StoreProposalRequest::class, storeProposalData()))->toHaveKey('bank_id');
});

test('na edição o banco excluído já gravado continua válido', function () {
    $proposal = createProposal($this->tenant, ['bank_id' => $this->bank->id, 'contract_type_id' => $this->withFinancing->id]);
    $this->tenant->run(fn () => $this->bank->delete());

    $errors = proposalRequestErrors(UpdateProposalRequest::class, array_merge(storeProposalData(), [
        'status' => ProposalStatus::IN_PROGRESS->value,
    ]), $proposal->id);

    expect($errors)->toBe([]);
});

test('status manual exige o motivo ou a previsão correspondente', function (string $status, string $field) {
    $proposal = createProposal($this->tenant, ['bank_id' => $this->bank->id, 'contract_type_id' => $this->withFinancing->id]);

    $errors = proposalRequestErrors(UpdateProposalRequest::class, array_merge(storeProposalData(), ['status' => $status]), $proposal->id);

    expect($errors)->toHaveKey($field);
})->with([
    'cancelada' => [ProposalStatus::CANCELED->value, 'cancellation_reason'],
    'com restrição' => [ProposalStatus::RESTRICTED->value, 'restriction_reason'],
    'aguardando imóvel (mês)' => [ProposalStatus::AWAITING_PROPERTY->value, 'expected_delivery_month'],
    'aguardando imóvel (ano)' => [ProposalStatus::AWAITING_PROPERTY->value, 'expected_delivery_year'],
]);

test('não é possível finalizar a proposta manualmente', function () {
    $proposal = createProposal($this->tenant, ['bank_id' => $this->bank->id, 'contract_type_id' => $this->withFinancing->id]);

    $errors = proposalRequestErrors(UpdateProposalRequest::class, array_merge(storeProposalData(), [
        'status' => ProposalStatus::FINISHED->value,
    ]), $proposal->id);

    expect($errors)->toHaveKey('status');
});

test('tipo de custo vinculado a cartório exige o cartório no lançamento manual', function () {
    [$withNotary, $withoutNotary] = $this->tenant->run(fn () => [
        CostType::factory()->create(['requires_notary' => true]),
        CostType::factory()->create(),
    ]);

    $payload = ['description' => 'Registro', 'amount' => 150035];

    expect(proposalRequestErrors(StoreProposalCostItemRequest::class, array_merge($payload, ['cost_type_id' => $withNotary->id])))
        ->toHaveKey('notary_id')
        ->and(proposalRequestErrors(StoreProposalCostItemRequest::class, array_merge($payload, ['cost_type_id' => $withoutNotary->id])))
        ->toBe([]);
});

test('criador e analista precisam ser da equipe e parceiros precisam ter o cargo Parceiro', function () {
    $outsider = $this->tenant->run(fn () => User::factory()->create());
    $partner = userWithRole($this->tenant, RolesEnum::PARTNER);

    $errors = proposalRequestErrors(StoreProposalRequest::class, storeProposalData([
        'creator_id' => $outsider->id,
        'analyst_id' => $outsider->id,
        'partner_ids' => [$outsider->id, $partner->id],
    ]));

    expect($errors)->toHaveKeys(['creator_id', 'analyst_id', 'partner_ids.0'])
        ->not->toHaveKey('partner_ids.1');
});

test('quem tem só cargo externo vira o criador da proposta que cadastra', function () {
    $partner = userWithRole($this->tenant, RolesEnum::PARTNER);

    $request = StoreProposalRequest::create('/', 'POST', storeProposalData(['creator_id' => $this->creator->id]));
    $request->setContainer(app())->setRedirector(app(Redirector::class))->setUserResolver(fn () => $partner);
    $request->validateResolved();

    expect($request->validated('creator_id'))->toBe($partner->id);
});

test('a edição aceita manter o criador e os parceiros atuais mesmo sem o cargo exigido', function () {
    $outsider = $this->tenant->run(fn () => User::factory()->create());
    $proposal = createProposal($this->tenant, [
        'creator_id' => $outsider->id,
        'bank_id' => $this->bank->id,
        'contract_type_id' => $this->withFinancing->id,
    ]);
    $this->tenant->run(fn () => $proposal->partners()->attach($outsider->id));

    $errors = proposalRequestErrors(UpdateProposalRequest::class, array_merge(storeProposalData(), [
        'creator_id' => $outsider->id,
        'partner_ids' => [$outsider->id],
        'status' => ProposalStatus::IN_PROGRESS->value,
    ]), $proposal->id);

    expect($errors)->toBe([]);
});
