<?php

use App\Enums\ContactTypeEnum;
use App\Exceptions\ContactHasFinancialEntriesException;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Supplier;
use App\Services\SuppliersService;

beforeEach(function () {
    $this->tenant = sharedTenant();

    $this->contact = $this->tenant->run(fn () => Contact::factory()->create());
});

function supplierPayload(array $overrides = []): array
{
    return array_merge([
        'responsible_person' => 'Maria Responsável',
        'description' => 'Fornecedor de materiais',
        'supply_category' => 'Materiais',
    ], $overrides);
}

test('store cria o vínculo de fornecedor com os dados informados', function () {
    app(SuppliersService::class)->store($this->contact, supplierPayload(), $this->tenant);

    $this->tenant->run(function () {
        $supplier = Supplier::where('contact_id', $this->contact->id)->first();

        expect($supplier)->not->toBeNull()
            ->and($supplier->responsible_person)->toBe('Maria Responsável')
            ->and($supplier->supply_category)->toBe('Materiais');
    });
});

test('fornecedor é criado ativo por padrão', function () {
    app(SuppliersService::class)->store($this->contact, supplierPayload(), $this->tenant);

    $this->tenant->run(function () {
        expect(Supplier::where('contact_id', $this->contact->id)->first()->active)->toBeTrue();
    });
});

test('setActive inativa e reativa o fornecedor preservando o registro', function () {
    app(SuppliersService::class)->store($this->contact, supplierPayload(), $this->tenant);

    $afterInactivate = app(SuppliersService::class)->setActive($this->tenant, (string) $this->contact->id, false);

    expect($afterInactivate)->toBeFalse();

    $this->tenant->run(function () {
        expect(Supplier::where('contact_id', $this->contact->id)->first()->active)->toBeFalse();
    });

    $afterReactivate = app(SuppliersService::class)->setActive($this->tenant, (string) $this->contact->id, true);

    expect($afterReactivate)->toBeTrue();
});

test('findAll do fornecedor expõe o campo active', function () {
    app(SuppliersService::class)->store($this->contact, supplierPayload(), $this->tenant);
    app(SuppliersService::class)->setActive($this->tenant, (string) $this->contact->id, false);

    $suppliers = app(SuppliersService::class)->findAll($this->tenant);

    expect($suppliers->first()['active'])->toBeFalse();
});

test('update altera os dados sem duplicar o vínculo', function () {
    app(SuppliersService::class)->store($this->contact, supplierPayload(), $this->tenant);

    app(SuppliersService::class)->update(
        $this->contact,
        supplierPayload(['supply_category' => 'Serviços']),
        $this->tenant
    );

    $this->tenant->run(function () {
        expect(Supplier::where('contact_id', $this->contact->id)->count())->toBe(1)
            ->and(Supplier::where('contact_id', $this->contact->id)->first()->supply_category)->toBe('Serviços');
    });
});

test('store restaura o vínculo soft-deletado em vez de estourar o índice único', function () {
    app(SuppliersService::class)->store($this->contact, supplierPayload(), $this->tenant);

    $originalId = $this->tenant->run(fn () => Supplier::where('contact_id', $this->contact->id)->first()->id);

    app(SuppliersService::class)->destroy($this->tenant, (string) $this->contact->id);

    app(SuppliersService::class)->store($this->contact, supplierPayload(['supply_category' => 'Serviços']), $this->tenant);

    $this->tenant->run(function () use ($originalId) {
        $supplier = Supplier::where('contact_id', $this->contact->id)->first();

        expect($supplier->id)->toBe($originalId)
            ->and($supplier->trashed())->toBeFalse()
            ->and($supplier->supply_category)->toBe('Serviços')
            ->and(Supplier::withTrashed()->where('contact_id', $this->contact->id)->count())->toBe(1);
    });
});

test('destroy remove o fornecedor e também o contato órfão', function () {
    app(SuppliersService::class)->store($this->contact, supplierPayload(), $this->tenant);

    app(SuppliersService::class)->destroy($this->tenant, (string) $this->contact->id);

    $this->tenant->run(function () {
        expect(Supplier::where('contact_id', $this->contact->id)->exists())->toBeFalse()
            ->and(Contact::find($this->contact->id))->toBeNull();
    });
});

test('destroy preserva o contato e o papel de cliente do mesmo contato', function () {
    app(SuppliersService::class)->store($this->contact, supplierPayload(), $this->tenant);

    $this->tenant->run(fn () => $this->contact->client()->create([]));

    app(SuppliersService::class)->destroy($this->tenant, (string) $this->contact->id);

    $this->tenant->run(function () {
        expect(Supplier::where('contact_id', $this->contact->id)->exists())->toBeFalse()
            ->and(Contact::find($this->contact->id))->not->toBeNull()
            ->and(Client::where('contact_id', $this->contact->id)->exists())->toBeTrue();
    });
});

test('destroy bloqueia a exclusão quando há lançamento financeiro como fornecedor', function () {
    app(SuppliersService::class)->store($this->contact, supplierPayload(), $this->tenant);

    createFinancialEntry($this->tenant, $this->contact->id, ContactTypeEnum::SUPPLIER, AccountPayable::class);

    expect(fn () => app(SuppliersService::class)->destroy($this->tenant, (string) $this->contact->id))
        ->toThrow(ContactHasFinancialEntriesException::class);

    $this->tenant->run(function () {
        expect(Supplier::where('contact_id', $this->contact->id)->exists())->toBeTrue();
    });
});

test('destroy permite excluir o fornecedor quando o lançamento financeiro é do papel de cliente', function () {
    app(SuppliersService::class)->store($this->contact, supplierPayload(), $this->tenant);

    $this->tenant->run(fn () => $this->contact->client()->create([]));

    createFinancialEntry($this->tenant, $this->contact->id, ContactTypeEnum::CLIENT, AccountReceivable::class);

    app(SuppliersService::class)->destroy($this->tenant, (string) $this->contact->id);

    $this->tenant->run(function () {
        expect(Supplier::where('contact_id', $this->contact->id)->exists())->toBeFalse()
            ->and(Contact::find($this->contact->id))->not->toBeNull();
    });
});

test('findAll ignora fornecedores cujo contato foi excluído', function () {
    app(SuppliersService::class)->store($this->contact, supplierPayload(), $this->tenant);

    $this->tenant->run(function () {
        $contact = Contact::factory()->create();
        $contact->supplier()->create(supplierPayload());
        $contact->delete();
    });

    $suppliers = app(SuppliersService::class)->findAll($this->tenant);

    expect($suppliers)->toHaveCount(1)
        ->and($suppliers->first()['contact']['id'])->toBe($this->contact->id)
        ->and($suppliers->first()['supply_category'])->toBe('Materiais');
});
