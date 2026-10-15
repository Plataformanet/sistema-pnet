<?php

use App\Enums\RegistryProtocolPosition;
use App\Http\Requests\IndexRegistryProtocolRequest;
use App\Http\Requests\StoreRegistryProtocolRequest;
use App\Http\Requests\UpdateRegistryProtocolRequest;
use App\Models\RegistryProtocol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

/**
 * @param  class-string<FormRequest>  $class
 * @param  array<string, mixed>  $data
 * @return array<string, array<int, string>>
 */
function registryProtocolRequestErrors(string $class, array $data, ?int $routeId = null): array
{
    $request = $class::create('/', 'POST', $data);
    $request->setContainer(app())->setRedirector(app(Redirector::class));

    if ($routeId !== null) {
        $route = new Route('PUT', 'documents/registry-protocols/{id}', fn () => null);
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
function registryProtocolData(array $overrides = []): array
{
    return array_merge([
        'protocol_number' => '998877',
        'buyer_name' => 'Maria Compradora',
        'seller_name' => '',
        'entered_at' => '2026-10-01',
        'exited_at' => '',
        'position' => RegistryProtocolPosition::IN_REGISTRY_PG->value,
    ], $overrides);
}

test('dados válidos passam, com vendedor e data de saída opcionais', function () {
    $this->tenant->run(fn () => expect(registryProtocolRequestErrors(StoreRegistryProtocolRequest::class, registryProtocolData()))->toBe([]));
});

test('posição inválida é recusada com mensagem em pt-BR', function () {
    $this->tenant->run(function () {
        $errors = registryProtocolRequestErrors(StoreRegistryProtocolRequest::class, registryProtocolData(['position' => 9]));

        expect($errors['position'])->toBe(['A posição selecionada é inválida.']);
    });
});

test('a data de saída não pode ser anterior à de entrada', function () {
    $this->tenant->run(function () {
        $errors = registryProtocolRequestErrors(StoreRegistryProtocolRequest::class, registryProtocolData(['exited_at' => '2026-09-30']));

        expect($errors['exited_at'])->toBe(['A data de saída não pode ser anterior à data de entrada.']);
    });
});

test('proposta inexistente ou excluída é recusada', function () {
    $proposal = createProposal($this->tenant);

    $this->tenant->run(function () use ($proposal) {
        $proposal->delete();

        expect(registryProtocolRequestErrors(StoreRegistryProtocolRequest::class, registryProtocolData(['proposal_id' => 999999]))['proposal_id'])
            ->toBe(['A proposta selecionada não existe ou foi excluída.'])
            ->and(registryProtocolRequestErrors(StoreRegistryProtocolRequest::class, registryProtocolData(['proposal_id' => $proposal->id])))
            ->toHaveKey('proposal_id');
    });
});

test('na edição a proposta já gravada continua aceita mesmo depois de excluída', function () {
    $proposal = createProposal($this->tenant);

    $this->tenant->run(function () use ($proposal) {
        $protocol = RegistryProtocol::factory()->withProposal($proposal)->create();
        $proposal->delete();

        $errors = registryProtocolRequestErrors(
            UpdateRegistryProtocolRequest::class,
            registryProtocolData(['proposal_id' => $proposal->id, 'protocol_number' => $protocol->protocol_number]),
            $protocol->id,
        );

        expect($errors)->toBe([]);
    });
});

test('o número do protocolo é único entre os protocolos ativos', function () {
    $this->tenant->run(function () {
        $existing = RegistryProtocol::factory()->create(['protocol_number' => 'ABC-1']);
        $deleted = RegistryProtocol::factory()->create(['protocol_number' => 'ABC-2']);
        $deleted->delete();

        expect(registryProtocolRequestErrors(StoreRegistryProtocolRequest::class, registryProtocolData(['protocol_number' => 'ABC-1']))['protocol_number'])
            ->toBe(['Já existe um protocolo com este número.'])
            ->and(registryProtocolRequestErrors(StoreRegistryProtocolRequest::class, registryProtocolData(['protocol_number' => 'ABC-2'])))
            ->toBe([])
            ->and(registryProtocolRequestErrors(UpdateRegistryProtocolRequest::class, registryProtocolData(['protocol_number' => 'ABC-1']), $existing->id))
            ->toBe([]);
    });
});

test('os textos são aparados antes da validação', function () {
    $this->tenant->run(function () {
        $request = formRequest(StoreRegistryProtocolRequest::class, registryProtocolData([
            'protocol_number' => '  555  ',
            'buyer_name' => '  Maria Compradora ',
            'seller_name' => '   ',
        ]));

        expect($request->validated())->toMatchArray([
            'protocol_number' => '555',
            'buyer_name' => 'Maria Compradora',
            'seller_name' => null,
        ]);
    });
});

test('o filtro da listagem só aceita as abas conhecidas', function () {
    $errors = registryProtocolRequestErrors(IndexRegistryProtocolRequest::class, ['status' => 'deleted', 'position' => 7]);

    expect($errors['status'])->toBe(['A aba selecionada é inválida.'])
        ->and($errors['position'])->toBe(['A posição selecionada é inválida.']);
});
