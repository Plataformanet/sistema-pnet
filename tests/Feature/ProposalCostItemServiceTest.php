<?php

use App\Enums\CostItemType;
use App\Enums\ReceiptType;
use App\Exceptions\CostItemNotRemovableException;
use App\Exceptions\ReceiptNotAllowedException;
use App\Mail\ProposalCostItemCreatedMail;
use App\Models\Applicant;
use App\Models\CostType;
use App\Models\Notary;
use App\Models\ProposalCostItem;
use App\Models\User;
use App\Services\ProposalCostItemService;
use App\Services\ReceiptService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->tenant = sharedTenant();
    Mail::fake();
    config(['bucket.disk' => 'public']);
    Storage::fake('public');

    $this->service = app(ProposalCostItemService::class);
    $this->proposal = createProposal($this->tenant);

    [$this->advisory, $this->registry, $this->notary] = $this->tenant->run(fn () => [
        CostType::where('name', 'Assessoria')->firstOrFail(),
        CostType::where('name', 'Registro')->firstOrFail(),
        Notary::factory()->create(['name' => '1º Registro de Imóveis']),
    ]);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function manualCostItem(array $overrides = [], ?UploadedFile $bill = null): ProposalCostItem
{
    return app(ProposalCostItemService::class)->storeManual((string) test()->proposal->id, array_merge([
        'cost_type_id' => test()->registry->id,
        'notary_id' => test()->notary->id,
        'description' => 'Registro da escritura',
        'date' => '2026-10-01',
        'amount' => 150035,
    ], $overrides), $bill, test()->tenant);
}

test('lançamento manual grava tipo Manual, valor em centavos e o boleto anexo', function () {
    $costItem = manualCostItem([], UploadedFile::fake()->create('boleto.pdf', 10, 'application/pdf'));

    $this->tenant->run(function () use ($costItem) {
        $fresh = $costItem->fresh();

        expect($fresh->type)->toBe(CostItemType::MANUAL)
            ->and($fresh->amount)->toBe(150035)
            ->and($fresh->has_bill)->toBeTrue();

        Storage::disk('public')->assertExists($fresh->bill_path);
    });
});

test('lançamento manual notifica parceiros e proponentes quando pedido', function () {
    $this->tenant->run(function () {
        $this->proposal->partners()->attach(User::factory()->create());
        $this->proposal->applicants()->attach(Applicant::factory()->create());
    });

    manualCostItem(['notify_partners' => true, 'notify_applicants' => true]);

    Mail::assertQueued(ProposalCostItemCreatedMail::class, 2);
});

test('editar o valor exige que a linha seja da proposta', function () {
    $costItem = manualCostItem();
    $other = createProposal($this->tenant);

    $this->service->updateAmount((string) $this->proposal->id, (string) $costItem->id, 99999, $this->tenant);
    $this->tenant->run(fn () => expect($costItem->fresh()->amount)->toBe(99999));

    $this->service->updateAmount((string) $other->id, (string) $costItem->id, 1, $this->tenant);
})->throws(ModelNotFoundException::class);

test('linhas da calculadora não podem ser excluídas', function () {
    $costItem = $this->tenant->run(fn () => $this->proposal->costItems()->create([
        'type' => CostItemType::EMOLUMENT,
        'description' => 'Registro de compra e venda',
        'amount' => 150000,
    ]));

    $this->service->delete((string) $this->proposal->id, (string) $costItem->id, $this->tenant);
})->throws(CostItemNotRemovableException::class);

test('excluir lançamento manual remove os anexos', function () {
    $costItem = manualCostItem([], UploadedFile::fake()->create('boleto.pdf', 10));
    $path = $this->tenant->run(fn () => $costItem->fresh()->bill_path);

    $this->service->delete((string) $this->proposal->id, (string) $costItem->id, $this->tenant);

    $this->tenant->run(fn () => expect(ProposalCostItem::find($costItem->id))->toBeNull());
    Storage::disk('public')->assertMissing($path);
});

test('o comprovante substitui o anterior', function () {
    $costItem = manualCostItem();

    $this->service->uploadProof((string) $this->proposal->id, (string) $costItem->id, UploadedFile::fake()->create('c1.pdf', 5), $this->tenant);
    $first = $this->tenant->run(fn () => $costItem->fresh()->proof_path);

    $this->service->uploadProof((string) $this->proposal->id, (string) $costItem->id, UploadedFile::fake()->create('c2.pdf', 5), $this->tenant);
    $second = $this->tenant->run(fn () => $costItem->fresh()->proof_path);

    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

test('total de emolumentos soma só emolumentos e taxas extras', function () {
    $this->tenant->run(function () {
        $this->proposal->costItems()->createMany([
            ['type' => CostItemType::EMOLUMENT, 'amount' => 100000],
            ['type' => CostItemType::EXTRA_FEE, 'amount' => 4510],
            ['type' => CostItemType::ITBI, 'amount' => 900000],
            ['type' => CostItemType::SERVICE, 'amount' => 150000],
        ]);

        expect($this->proposal->feesTotal())->toBe(104510);
    });
});

test('recibo vinculado usa o tipo do custo e grava o nome do cartório como texto', function () {
    $costItem = manualCostItem(['cost_type_id' => $this->advisory->id]);

    $receipt = app(ReceiptService::class)->store((string) $this->proposal->id, [
        'proposal_cost_item_id' => $costItem->id,
        'name' => 'Maria',
        'document' => '529.982.247-25',
        'date' => '2026-10-01',
    ], $this->tenant);

    $this->tenant->run(fn () => $this->notary->update(['name' => 'Nome alterado']));

    $this->tenant->run(function () use ($receipt) {
        $fresh = $receipt->fresh();

        expect($fresh->type)->toBe(ReceiptType::ADVISORY)
            ->and($fresh->document)->toBe('52998224725')
            ->and($fresh->notary_name)->toBe('1º Registro de Imóveis')
            ->and($fresh->total_spent)->toBe(150035);
    });
});

test('cobrança que não gera recibo é bloqueada', function () {
    $costItem = manualCostItem();

    app(ReceiptService::class)->store((string) $this->proposal->id, [
        'proposal_cost_item_id' => $costItem->id,
        'name' => 'Maria',
        'document' => '52998224725',
        'date' => '2026-10-01',
    ], $this->tenant);
})->throws(ReceiptNotAllowedException::class);

test('recibo geral não precisa de cobrança', function () {
    $receipt = app(ReceiptService::class)->store((string) $this->proposal->id, [
        'name' => 'Maria',
        'document' => '52998224725',
        'total_spent' => 500000,
        'amount_deposited' => 600000,
        'date' => '2026-10-01',
    ], $this->tenant);

    expect($receipt->type)->toBe(ReceiptType::GENERAL)
        ->and($receipt->amount_deposited)->toBe(600000);
});
