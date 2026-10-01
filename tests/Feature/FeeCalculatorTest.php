<?php

use App\Actions\FeeCalculator\CalculateFees;
use App\Enums\CalculationType;
use App\Enums\CostItemType;
use App\Enums\FeeDiscount;
use App\Enums\ItbiModule;
use App\Enums\RolesEnum;
use App\Enums\SupportedState;
use App\Exceptions\Itbi\ItbiNotConfiguredException;
use App\Exceptions\RegistryFee\RegistryFeeRejectedException;
use App\Exceptions\RegistryFee\RegistryFeeUnavailableException;
use App\Http\Requests\CalculateFeesRequest;
use App\Models\BillableService;
use App\Models\FeeCalculation;
use App\Models\ItbiMunicipality;
use App\Models\ItbiRate;
use App\Models\User;
use App\Policies\FeeCalculationPolicy;
use App\Services\FeeCalculationService;
use App\Services\FeeCalculator\CostItemsBuilder;
use App\Services\FeeCalculator\FeeBreakdown;
use App\Services\Ibge\IbgeLocalityClient;
use App\Services\RegistryFee\RegistryFeeRequestData;
use App\Services\RegistryFee\RegistryFeeResult;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->tenant = sharedTenant();
    Http::preventStrayRequests();

    config(['services.registry_fee_calculator.url' => 'https://calculadora.test/api']);

    [$this->user, $this->municipality] = $this->tenant->run(function () {
        $municipality = ItbiMunicipality::factory()->create(['name' => 'São Paulo', 'ibge_code' => 3550308, 'module' => ItbiModule::STANDARD]);
        $municipality->rate()->save(new ItbiRate(['own_funds_rate' => 3, 'financed_rate' => 0.5]));

        return [User::factory()->create(), $municipality];
    });
});

/**
 * @return array<string, mixed>
 */
function apiResult(): array
{
    return [
        'atos' => [
            ['descricao' => 'Registro de compra e venda', 'emolumentos' => 1234.56, 'fundos' => 265.79, 'subtotal' => 1500.35],
        ],
        'taxas_extras' => [['descricao' => 'Prenotação', 'valor' => 45.10]],
        'total' => 1545.45,
        'extra_information' => '<p onclick="alert(1)">Observação</p><script>alert(1)</script>',
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function calculationData(array $overrides = []): array
{
    return array_merge([
        'state' => 'SP',
        'municipality_ibge_code' => 3550308,
        'municipality_name' => 'São Paulo',
        'type' => CalculationType::PURCHASE_WITH_FIDUCIARY_LIEN->value,
        'property_value' => 40_000_099,
        'financing_value' => 30_000_000,
        'financing_system' => 'sfh',
        'discount' => FeeDiscount::FIRST_ACQUISITION_SFH->value,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $data
 * @return array<string, array<int, string>>
 */
function calculationRequestErrors(array $data): array
{
    $request = CalculateFeesRequest::create('/', 'POST', $data);
    $request->setContainer(app())->setRedirector(app(Redirector::class));

    try {
        $request->validateResolved();
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    return [];
}

test('cálculo com sucesso grava o retrato com valores em centavos e ITBI', function () {
    Http::fake(['calculadora.test/*' => Http::response(['result' => apiResult()])]);

    $calculation = app(CalculateFees::class)->handle(calculationData(), $this->user, $this->tenant);

    $this->tenant->run(function () use ($calculation) {
        $fresh = FeeCalculation::findOrFail($calculation->id);

        expect($fresh->fees_total)->toBe(154545)
            ->and($fresh->itbi_amount)->toBe(450_003)
            ->and($fresh->user_id)->toBe($this->user->id)
            ->and($fresh->expires_at->isFuture())->toBeTrue()
            ->and($fresh->api_result['extra_information'])->toBe('<p>Observação</p>alert(1)');
    });
});

test('o payload enviado à API tem consulta, município, valores em reais inteiros e desconto', function () {
    Http::fake(['calculadora.test/*' => Http::response(['result' => apiResult()])]);

    app(CalculateFees::class)->handle(calculationData(), $this->user, $this->tenant);

    Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://calculadora.test/api/calculate'
        && $request['consulta_id'] === 2
        && $request['codigo_municipio'] === 3550308
        && $request['valor_imovel'] === 400_000
        && $request['valor_financiamento'] === 300_000
        && $request['desconto'] === 'SFH/SP');
});

test('a conversão para reais trunca os centavos num único lugar', function () {
    $payload = (new RegistryFeeRequestData(CalculationType::GENERAL_REGISTRATION, SupportedState::SP, 3550308, 35_000_099, 10_000_000))->toPayload();

    expect($payload['valor_imovel'])->toBe(350_000)
        ->and($payload['valor_financiamento'])->toBe(0)
        ->and($payload)->not->toHaveKey('desconto');
});

test('erro de negócio da API vira exceção com a mensagem da API', function () {
    Http::fake(['calculadora.test/*' => Http::response(['errorMessage' => 'Valor do imóvel inválido para o município'], 422)]);

    expect(fn () => app(CalculateFees::class)->handle(calculationData(), $this->user, $this->tenant))
        ->toThrow(RegistryFeeRejectedException::class, 'Valor do imóvel inválido para o município');

    $this->tenant->run(fn () => expect(FeeCalculation::count())->toBe(0));
});

test('API fora do ar ou sem autorização vira indisponibilidade e nada é gravado', function (int $status) {
    Http::fake(['calculadora.test/*' => Http::response(['message' => 'Unauthenticated.'], $status)]);

    expect(fn () => app(CalculateFees::class)->handle(calculationData(), $this->user, $this->tenant))
        ->toThrow(RegistryFeeUnavailableException::class);

    $this->tenant->run(fn () => expect(FeeCalculation::count())->toBe(0));
})->with([401, 500]);

test('município sem cadastro de ITBI falha antes de chamar a API', function () {
    Http::fake();

    expect(fn () => app(CalculateFees::class)->handle(calculationData(['municipality_ibge_code' => 3548708]), $this->user, $this->tenant))
        ->toThrow(ItbiNotConfiguredException::class);

    Http::assertNothingSent();
});

test('averbação não exige cadastro de ITBI', function () {
    Http::fake(['calculadora.test/*' => Http::response(['result' => apiResult()])]);

    $calculation = app(CalculateFees::class)->handle(calculationData([
        'state' => 'RJ',
        'municipality_ibge_code' => 3304557,
        'type' => CalculationType::ECONOMIC_VALUE_ANNOTATION->value,
    ]), $this->user, $this->tenant);

    expect($calculation->itbi_amount)->toBeNull();
});

test('a resposta da API é convertida para centavos sem perda', function () {
    $result = RegistryFeeResult::fromArray(apiResult());

    expect($result->acts[0]['subtotal'])->toBe(150035)
        ->and($result->acts[0]['descricao'])->toBe('Registro de compra e venda')
        ->and($result->extraFees[0]['amount'])->toBe(4510)
        ->and($result->total)->toBe(154545);
});

test('averbação fora do RJ e compra no PA são rejeitadas', function () {
    expect(calculationRequestErrors(calculationData(['type' => CalculationType::ECONOMIC_VALUE_ANNOTATION->value])))->toHaveKey('type')
        ->and(calculationRequestErrors(calculationData(['state' => 'PA'])))->toHaveKey('type');
});

test('desconto de outra UF é rejeitado e financiamento não pode passar do valor do imóvel', function () {
    expect(calculationRequestErrors(calculationData(['discount' => FeeDiscount::POPULAR_HOUSING->value])))->toHaveKey('discount')
        ->and(calculationRequestErrors(calculationData(['financing_value' => 50_000_000])))->toHaveKey('financing_value')
        ->and(calculationRequestErrors(calculationData(['property_value' => '400.000,00'])))->toHaveKey('property_value');
});

test('primeiro imóvel é obrigatório na compra em município do módulo 03', function () {
    $this->tenant->run(fn () => $this->municipality->update(['module' => ItbiModule::FIRST_PROPERTY_RATE]));

    expect(calculationRequestErrors(calculationData()))->toHaveKey('first_property')
        ->and(calculationRequestErrors(calculationData(['first_property' => true])))->toBe([]);
});

test('CostItemsBuilder gera atos, taxas extras, serviços e ITBI só quando há ITBI', function () {
    $service = new BillableService(['name' => 'Assessoria', 'description' => 'Assessoria completa', 'price' => 200000, 'generates_receipt' => true]);
    $result = RegistryFeeResult::fromArray(apiResult());
    $builder = new CostItemsBuilder;

    $items = collect($builder->build($result, CalculationType::GENERAL_REGISTRATION, 450000, [['service' => $service, 'amount' => 150000]]));

    expect($items->pluck('type')->all())->toBe([CostItemType::EMOLUMENT, CostItemType::EXTRA_FEE, CostItemType::SERVICE, CostItemType::ITBI])
        ->and($items[0]['amount'])->toBe(150035)
        ->and($items[1]['extra_fee_description'])->toBe('Prenotação')
        ->and($items[2]['amount'])->toBe(150000)
        ->and($items[2]['generates_receipt'])->toBeTrue()
        ->and($items[3]['amount'])->toBe(450000);

    $annotation = collect($builder->build($result, CalculationType::ECONOMIC_VALUE_ANNOTATION, null));

    expect($annotation->pluck('type'))->not->toContain(CostItemType::ITBI);
});

test('o total geral soma cálculo, ITBI e serviços e aparece sempre que houver ITBI ou serviço', function () {
    $breakdown = FeeBreakdown::fromApiResult(apiResult(), 450000, [['name' => 'Assessoria', 'amount' => 150000]])->toArray();

    expect($breakdown['grand_total'])->toBe(154545 + 450000 + 150000)
        ->and($breakdown['show_grand_total'])->toBeTrue()
        ->and($breakdown['subtotals']['subtotal'])->toBe(150035)
        ->and(FeeBreakdown::fromApiResult(apiResult(), null)->toArray()['show_grand_total'])->toBeFalse();
});

test('as colunas seguem a ordem da calculadora de origem, independente da ordem das chaves gravadas', function () {
    $act = ['ipesp' => 136.45, 'subtotal' => 1155.95, 'descricao' => 'Registro', 'emolumento' => 701.42, 'estado(sp)' => 199.35, 'registro_civil' => 36.92, 'ministerio_publico' => 33.67, 'tribunal_de_justica' => 48.14];

    $columns = FeeBreakdown::fromApiResult(['atos' => [$act], 'total' => 1155.95], null)->toArray()['columns'];

    expect(array_column($columns, 'key'))->toBe(['descricao', 'emolumento', 'estado(sp)', 'ipesp', 'registro_civil', 'tribunal_de_justica', 'ministerio_publico', 'subtotal']);
});

test('o formulário de cálculo traz o módulo de ITBI do município e as opções da tela', function () {
    $props = app(FeeCalculationService::class)->formProps(CalculationType::PURCHASE_WITH_FIDUCIARY_LIEN, SupportedState::SP, 3550308, 'São Paulo', $this->tenant);

    expect($props['type']['value'])->toBe(CalculationType::PURCHASE_WITH_FIDUCIARY_LIEN->value)
        ->and($props['state'])->toBe('SP')
        ->and($props['municipality'])->toBe(['ibge_code' => 3550308, 'name' => 'São Paulo', 'itbi_module' => ItbiModule::STANDARD->value])
        ->and($props['discounts'])->not->toBeEmpty()
        ->and($props['financingSystems'])->not->toBeEmpty();

    expect(app(FeeCalculationService::class)->formProps(CalculationType::GENERAL_REGISTRATION, null, null, null, $this->tenant)['municipality']['itbi_module'])->toBeNull();
});

test('a lista de municípios do IBGE funciona no tenant com um cache sem suporte a tags', function () {
    config(['cache.default' => 'database']);
    Http::fake(['servicodados.ibge.gov.br/*' => Http::response([
        ['id' => 3550308, 'nome' => 'São Paulo'],
        ['id' => 3509502, 'nome' => 'Campinas'],
    ])]);

    $municipalities = app(IbgeLocalityClient::class)->municipalities(SupportedState::SP);

    expect($municipalities->all())->toBe([
        ['ibge_code' => 3509502, 'name' => 'Campinas'],
        ['ibge_code' => 3550308, 'name' => 'São Paulo'],
    ]);
});

test('só o dono ou o administrador vê o cálculo', function () {
    Http::fake(['calculadora.test/*' => Http::response(['result' => apiResult()])]);
    $calculation = app(CalculateFees::class)->handle(calculationData(), $this->user, $this->tenant);

    $other = $this->tenant->run(fn () => User::factory()->create());
    $admin = userWithRole($this->tenant, RolesEnum::ADMIN);
    $policy = new FeeCalculationPolicy;

    $this->tenant->run(function () use ($policy, $calculation, $other, $admin) {
        expect($policy->view($this->user, $calculation))->toBeTrue()
            ->and($policy->view($other, $calculation))->toBeFalse()
            ->and($policy->view($admin, $calculation))->toBeTrue();
    });
});
