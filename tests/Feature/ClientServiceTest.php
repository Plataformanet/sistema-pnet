<?php

use App\Enums\ContactTypeEnum;
use App\Exceptions\ContactHasFinancialEntriesException;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Address;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Supplier;
use App\Services\ClientService;

beforeEach(function () {
    $this->tenant = sharedTenant();

    $this->contact = $this->tenant->run(fn () => Contact::factory()->create());
});

test('store cria o vínculo de cliente do contato', function () {
    $client = app(ClientService::class)->store($this->contact, [], $this->tenant);

    expect($client)->toBeInstanceOf(Client::class)
        ->and($client->contact_id)->toBe($this->contact->id)
        ->and($client->active)->toBeTrue();
});

test('setActive inativa e reativa o cliente preservando o registro', function () {
    app(ClientService::class)->store($this->contact, [], $this->tenant);

    $afterInactivate = app(ClientService::class)->setActive($this->tenant, (string) $this->contact->id, false);

    expect($afterInactivate)->toBeFalse();

    $this->tenant->run(function () {
        expect(Client::where('contact_id', $this->contact->id)->first()->active)->toBeFalse();
    });

    $afterReactivate = app(ClientService::class)->setActive($this->tenant, (string) $this->contact->id, true);

    expect($afterReactivate)->toBeTrue();
});

test('setActive do cliente não afeta o papel de fornecedor do mesmo contato', function () {
    app(ClientService::class)->store($this->contact, [], $this->tenant);

    $this->tenant->run(fn () => Supplier::create([
        'contact_id' => $this->contact->id,
        'responsible_person' => 'Responsável',
        'description' => 'Fornecedor de teste',
        'supply_category' => 'Materiais',
    ]));

    app(ClientService::class)->setActive($this->tenant, (string) $this->contact->id, false);

    $this->tenant->run(function () {
        expect(Client::where('contact_id', $this->contact->id)->first()->active)->toBeFalse()
            ->and(Supplier::where('contact_id', $this->contact->id)->first()->active)->toBeTrue();
    });
});

test('findAll expõe o campo active', function () {
    app(ClientService::class)->store($this->contact, [], $this->tenant);
    app(ClientService::class)->setActive($this->tenant, (string) $this->contact->id, false);

    $clients = app(ClientService::class)->findAll($this->tenant);

    expect($clients->first()['active'])->toBeFalse();
});

test('store é idempotente e não duplica o vínculo do mesmo contato', function () {
    $first = app(ClientService::class)->store($this->contact, [], $this->tenant);
    $second = app(ClientService::class)->store($this->contact, [], $this->tenant);

    expect($second->id)->toBe($first->id);

    $this->tenant->run(function () {
        expect(Client::where('contact_id', $this->contact->id)->count())->toBe(1);
    });
});

test('store restaura o vínculo soft-deletado em vez de estourar o índice único', function () {
    $original = app(ClientService::class)->store($this->contact, [], $this->tenant);

    app(ClientService::class)->destroy($this->tenant, (string) $this->contact->id);

    $restored = app(ClientService::class)->store($this->contact, [], $this->tenant);

    expect($restored->id)->toBe($original->id)
        ->and($restored->trashed())->toBeFalse();

    $this->tenant->run(function () {
        expect(Client::withTrashed()->where('contact_id', $this->contact->id)->count())->toBe(1);
    });
});

test('destroy remove o cliente e também o contato órfão com o endereço', function () {
    app(ClientService::class)->store($this->contact, [], $this->tenant);

    $this->tenant->run(fn () => Address::factory()->create(['contact_id' => $this->contact->id]));

    app(ClientService::class)->destroy($this->tenant, (string) $this->contact->id);

    $this->tenant->run(function () {
        expect(Client::where('contact_id', $this->contact->id)->exists())->toBeFalse()
            ->and(Contact::find($this->contact->id))->toBeNull()
            ->and(Contact::withTrashed()->find($this->contact->id)->trashed())->toBeTrue()
            ->and(Address::where('contact_id', $this->contact->id)->exists())->toBeFalse();
    });
});

test('destroy preserva o contato e o papel de fornecedor do mesmo contato', function () {
    app(ClientService::class)->store($this->contact, [], $this->tenant);

    $this->tenant->run(fn () => Supplier::create([
        'contact_id' => $this->contact->id,
        'responsible_person' => 'Responsável',
        'description' => 'Fornecedor de teste',
        'supply_category' => 'Materiais',
    ]));

    app(ClientService::class)->destroy($this->tenant, (string) $this->contact->id);

    $this->tenant->run(function () {
        expect(Client::where('contact_id', $this->contact->id)->exists())->toBeFalse()
            ->and(Contact::find($this->contact->id))->not->toBeNull()
            ->and(Supplier::where('contact_id', $this->contact->id)->exists())->toBeTrue();
    });
});

test('destroy bloqueia a exclusão quando há lançamento financeiro como cliente', function () {
    app(ClientService::class)->store($this->contact, [], $this->tenant);

    createFinancialEntry($this->tenant, $this->contact->id, ContactTypeEnum::CLIENT, AccountReceivable::class);

    expect(fn () => app(ClientService::class)->destroy($this->tenant, (string) $this->contact->id))
        ->toThrow(ContactHasFinancialEntriesException::class);

    $this->tenant->run(function () {
        expect(Client::where('contact_id', $this->contact->id)->exists())->toBeTrue()
            ->and(Contact::find($this->contact->id))->not->toBeNull();
    });
});

test('destroy permite excluir o cliente quando o lançamento financeiro é do papel de fornecedor', function () {
    app(ClientService::class)->store($this->contact, [], $this->tenant);

    $this->tenant->run(fn () => Supplier::create([
        'contact_id' => $this->contact->id,
        'responsible_person' => 'Responsável',
        'description' => 'Fornecedor de teste',
        'supply_category' => 'Materiais',
    ]));

    createFinancialEntry($this->tenant, $this->contact->id, ContactTypeEnum::SUPPLIER, AccountPayable::class);

    app(ClientService::class)->destroy($this->tenant, (string) $this->contact->id);

    $this->tenant->run(function () {
        expect(Client::where('contact_id', $this->contact->id)->exists())->toBeFalse()
            ->and(Contact::find($this->contact->id))->not->toBeNull();
    });
});

test('destroy preserva o contato sem papéis quando ainda existe lançamento financeiro', function () {
    app(ClientService::class)->store($this->contact, [], $this->tenant);

    createFinancialEntry($this->tenant, $this->contact->id, ContactTypeEnum::SUPPLIER, AccountPayable::class);

    app(ClientService::class)->destroy($this->tenant, (string) $this->contact->id);

    $this->tenant->run(function () {
        expect(Client::where('contact_id', $this->contact->id)->exists())->toBeFalse()
            ->and(Contact::find($this->contact->id))->not->toBeNull();
    });
});

test('findAll ignora clientes cujo contato foi excluído', function () {
    app(ClientService::class)->store($this->contact, [], $this->tenant);

    $orphan = $this->tenant->run(function () {
        $contact = Contact::factory()->create();
        $contact->client()->create([]);
        $contact->delete();

        return $contact;
    });

    $clients = app(ClientService::class)->findAll($this->tenant);

    expect($clients)->toHaveCount(1)
        ->and($clients->first()['contact']['id'])->toBe($this->contact->id)
        ->and($clients->pluck('contact.id'))->not->toContain($orphan->id);
});

test('findByContactId retorna o cliente com contato e endereço carregados', function () {
    app(ClientService::class)->store($this->contact, [], $this->tenant);

    $this->tenant->run(fn () => Address::factory()->create(['contact_id' => $this->contact->id]));

    $client = app(ClientService::class)->findByContactId((string) $this->contact->id, $this->tenant);

    expect($client->contact_id)->toBe($this->contact->id)
        ->and($client->relationLoaded('contact'))->toBeTrue()
        ->and($client->contact->relationLoaded('address'))->toBeTrue();
});
