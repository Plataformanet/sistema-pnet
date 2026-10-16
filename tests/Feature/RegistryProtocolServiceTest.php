<?php

use App\Enums\RegistryProtocolPosition;
use App\Enums\RolesEnum;
use App\Models\Applicant;
use App\Models\Contact;
use App\Models\RegistryProtocol;
use App\Models\Seller;
use App\Services\RegistryProtocolService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->tenant = sharedTenant();
    $this->service = app(RegistryProtocolService::class);
    $this->admin = userWithRole($this->tenant, RolesEnum::ADMIN);
});

test('store grava o protocolo com a data de saída nula quando não informada', function () {
    $protocol = $this->service->store([
        'protocol_number' => '123456',
        'buyer_name' => 'Maria Compradora',
        'seller_name' => null,
        'entered_at' => '2026-10-01',
        'exited_at' => null,
        'position' => RegistryProtocolPosition::UNDER_ANALYSIS->value,
    ], $this->tenant);

    $this->tenant->run(function () use ($protocol) {
        $fresh = $protocol->fresh();

        expect($fresh->exited_at)->toBeNull()
            ->and($fresh->position)->toBe(RegistryProtocolPosition::UNDER_ANALYSIS)
            ->and($fresh->entered_at->toDateString())->toBe('2026-10-01');
    });
});

test('a aba padrão mostra só os protocolos em aberto e a de encerrados mostra os em pagamento do banco e concluídos', function () {
    [$open, $closed, $completed] = $this->tenant->run(fn () => [
        RegistryProtocol::factory()->requirement()->create(),
        RegistryProtocol::factory()->awaitingBankPayment()->create(),
        RegistryProtocol::factory()->completed()->create(),
    ]);

    $openIds = collect($this->service->paginate([], $this->tenant)->items())->pluck('id');
    $closedIds = collect($this->service->paginate(['status' => 'closed'], $this->tenant)->items())->pluck('id');
    $allIds = collect($this->service->paginate(['status' => 'all'], $this->tenant)->items())->pluck('id');

    expect($openIds)->toContain($open->id)->not->toContain($closed->id, $completed->id)
        ->and($closedIds)->toContain($closed->id, $completed->id)->not->toContain($open->id)
        ->and($allIds)->toContain($open->id, $closed->id, $completed->id);
});

test('os contadores das abas vêm agrupados por posição', function () {
    $this->tenant->run(function () {
        RegistryProtocol::factory()->count(2)->create();
        RegistryProtocol::factory()->underAnalysis()->create();
        RegistryProtocol::factory()->awaitingBankPayment()->create();
        RegistryProtocol::factory()->completed()->create();
    });

    expect($this->service->tabCounts([], $this->tenant))->toBe(['open' => 3, 'closed' => 2, 'all' => 5]);
});

test('a listagem busca por número, comprador, vendedor e nº da proposta', function () {
    $proposal = createProposal($this->tenant);

    [$byNumber, $byBuyer, $byProposal] = $this->tenant->run(fn () => [
        RegistryProtocol::factory()->create(['protocol_number' => 'PRT-7788']),
        RegistryProtocol::factory()->create(['buyer_name' => 'Joaquina Teixeira']),
        RegistryProtocol::factory()->withProposal($proposal)->create(),
    ]);

    $search = fn (string $term) => collect($this->service->paginate(['search' => $term, 'status' => 'all'], $this->tenant)->items())->pluck('id');

    expect($search('7788'))->toContain($byNumber->id)
        ->and($search('Joaquina'))->toContain($byBuyer->id)->not->toContain($byNumber->id)
        ->and($search(str_pad((string) $proposal->id, 5, '0', STR_PAD_LEFT)))->toContain($byProposal->id);
});

test('a listagem filtra por posição e por proposta', function () {
    $proposal = createProposal($this->tenant);

    [$linked, $other] = $this->tenant->run(fn () => [
        RegistryProtocol::factory()->underAnalysis()->withProposal($proposal)->create(),
        RegistryProtocol::factory()->requirement()->create(),
    ]);

    $byPosition = collect($this->service->paginate(['position' => RegistryProtocolPosition::REQUIREMENT->value], $this->tenant)->items())->pluck('id');
    $byProposal = collect($this->service->paginate(['proposal_id' => $proposal->id], $this->tenant)->items())->pluck('id');

    expect($byPosition)->toContain($other->id)->not->toContain($linked->id)
        ->and($byProposal->all())->toBe([$linked->id]);
});

test('a linha da listagem traz a proposta com 5 dígitos e marca a proposta excluída', function () {
    $proposal = createProposal($this->tenant);

    $this->tenant->run(function () use ($proposal) {
        RegistryProtocol::factory()->withProposal($proposal)->create();
        $proposal->delete();
    });

    $row = $this->service->paginate(['proposal_id' => $proposal->id], $this->tenant)->items()[0];

    expect($row['proposal'])->toBe([
        'id' => $proposal->id,
        'number' => str_pad((string) $proposal->id, 5, '0', STR_PAD_LEFT),
        'deleted' => true,
    ])->and($row['position']['label'])->toBe('Em registro PG');
});

test('excluir faz soft delete e o protocolo some da listagem', function () {
    $protocol = $this->tenant->run(fn () => RegistryProtocol::factory()->create());

    $this->service->delete((string) $protocol->id, $this->tenant);

    $this->tenant->run(fn () => expect(RegistryProtocol::withTrashed()->find($protocol->id)->trashed())->toBeTrue());
    expect(collect($this->service->paginate(['status' => 'all'], $this->tenant)->items())->pluck('id'))->not->toContain($protocol->id);
});

test('excluir a proposta não apaga os protocolos dela', function () {
    $softDeleted = createProposal($this->tenant);
    $forceDeleted = createProposal($this->tenant);

    [$keptLinked, $orphaned] = $this->tenant->run(fn () => [
        RegistryProtocol::factory()->withProposal($softDeleted)->create(),
        RegistryProtocol::factory()->withProposal($forceDeleted)->create(),
    ]);

    $this->tenant->run(function () use ($softDeleted, $forceDeleted, $keptLinked, $orphaned) {
        $softDeleted->delete();
        $forceDeleted->forceDelete();

        expect($keptLinked->fresh()->proposal_id)->toBe($softDeleted->id)
            ->and($orphaned->fresh())->not->toBeNull()
            ->and($orphaned->fresh()->proposal_id)->toBeNull();
    });
});

test('o preenchimento traz todos os proponentes e todos os vendedores da proposta', function () {
    $proposal = createProposal($this->tenant);

    $this->tenant->run(function () use ($proposal) {
        $proposal->applicants()->attach([
            Applicant::factory()->create(['contact_id' => Contact::factory()->create(['name_corporatereason' => 'Ana Lima'])])->id,
            Applicant::factory()->create(['contact_id' => Contact::factory()->create(['name_corporatereason' => 'Bruno Lima'])])->id,
        ]);
        $proposal->sellers()->attach([
            Seller::factory()->create(['contact_id' => Contact::factory()->create(['name_corporatereason' => 'Construtora Alfa'])])->id,
            Seller::factory()->create(['contact_id' => Contact::factory()->create(['name_corporatereason' => 'Carla Souza'])])->id,
        ]);
    });

    $found = $this->service->findProposalForParties((string) $proposal->id, $this->admin, $this->tenant);
    $parties = $this->tenant->run(fn () => $this->service->partiesFor($found));

    expect(explode(', ', $parties['buyer_name']))->toEqualCanonicalizing(['Ana Lima', 'Bruno Lima'])
        ->and(explode(', ', $parties['seller_name']))->toEqualCanonicalizing(['Construtora Alfa', 'Carla Souza']);
});

test('proposta sem vendedor devolve o vendedor nulo', function () {
    $proposal = createProposal($this->tenant);

    $found = $this->service->findProposalForParties((string) $proposal->id, $this->admin, $this->tenant);

    expect($this->tenant->run(fn () => $this->service->partiesFor($found)))->toBe(['buyer_name' => '', 'seller_name' => null]);
});

test('as partes de proposta inexistente dão 404', function () {
    $this->service->findProposalForParties('999999', $this->admin, $this->tenant);
})->throws(ModelNotFoundException::class);

test('as partes de proposta excluída dão 404', function () {
    $proposal = createProposal($this->tenant);
    $this->tenant->run(fn () => $proposal->delete());

    $this->service->findProposalForParties((string) $proposal->id, $this->admin, $this->tenant);
})->throws(ModelNotFoundException::class);

test('as partes de proposta fora do alcance do parceiro dão 404', function () {
    $partner = userWithRole($this->tenant, RolesEnum::PARTNER);
    $proposal = createProposal($this->tenant);

    $this->service->findProposalForParties((string) $proposal->id, $partner, $this->tenant);
})->throws(ModelNotFoundException::class);

test('a busca de propostas respeita a visibilidade do usuário e o formato do combobox', function () {
    $partner = userWithRole($this->tenant, RolesEnum::PARTNER);
    $own = createProposal($this->tenant, ['creator_id' => $partner->id]);
    $other = createProposal($this->tenant);

    $forPartner = $this->service->searchProposals(null, $partner, $this->tenant);
    $forAdmin = $this->service->searchProposals((string) $other->id, $this->admin, $this->tenant);

    expect($forPartner->pluck('id')->all())->toBe([$own->id])
        ->and($forAdmin->pluck('id'))->toContain($other->id)
        ->and($forAdmin->first())->toHaveKeys(['id', 'number', 'applicants', 'name_corporatereason']);
});

test('a proposta pré-selecionada só é aceita quando o usuário a vê', function () {
    $partner = userWithRole($this->tenant, RolesEnum::PARTNER);
    $proposal = createProposal($this->tenant);

    expect($this->service->proposalOption($proposal->id, $this->admin, $this->tenant)['id'])->toBe($proposal->id)
        ->and($this->service->proposalOption($proposal->id, $partner, $this->tenant))->toBeNull()
        ->and($this->service->proposalOption(null, $this->admin, $this->tenant))->toBeNull();
});

test('o painel da timeline mostra o protocolo mais recente e quantos anteriores existem', function () {
    $proposal = createProposal($this->tenant);

    $latest = $this->tenant->run(function () use ($proposal) {
        RegistryProtocol::factory()->withProposal($proposal)->create(['entered_at' => '2026-08-01']);
        RegistryProtocol::factory()->withProposal($proposal)->create(['entered_at' => '2026-09-01']);
        RegistryProtocol::factory()->withProposal($proposal)->create(['entered_at' => '2026-10-10'])->delete();

        return RegistryProtocol::factory()->withProposal($proposal)->create(['entered_at' => '2026-09-01']);
    });

    $panel = $this->service->panelFor($proposal, $this->tenant);

    expect($panel['id'])->toBe($latest->id)
        ->and($panel['previous_count'])->toBe(2);
});

test('o painel da timeline é nulo quando a proposta não tem protocolo', function () {
    $proposal = createProposal($this->tenant);

    expect($this->service->panelFor($proposal, $this->tenant))->toBeNull();
});
