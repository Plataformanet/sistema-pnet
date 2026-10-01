<?php

use App\Enums\DocumentOwner;
use App\Enums\DocumentType;
use App\Models\Applicant;
use App\Models\ProposalDocument;
use App\Models\Seller;
use App\Models\User;
use App\Services\ProposalDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->tenant = sharedTenant();
    config(['bucket.disk' => 'public']);
    Storage::fake('public');

    $this->service = app(ProposalDocumentService::class);
    $this->user = $this->tenant->run(fn () => User::factory()->create());
    $this->proposal = createProposal($this->tenant);

    [$this->applicant, $this->seller] = $this->tenant->run(function () {
        $applicant = Applicant::factory()->create();
        $seller = Seller::factory()->create();
        $this->proposal->applicants()->attach($applicant);
        $this->proposal->sellers()->attach($seller);

        return [$applicant, $seller];
    });
});

test('store grava os arquivos no disco privado sob a pasta da proposta', function () {
    $documents = $this->service->store((string) $this->proposal->id, [
        'owner' => DocumentOwner::BUYER->value,
        'person_id' => $this->applicant->id,
        'type' => DocumentType::ID_DOCUMENT->value,
    ], [
        UploadedFile::fake()->create('rg.pdf', 20, 'application/pdf'),
        UploadedFile::fake()->image('rg-verso.jpg'),
    ], $this->user, $this->tenant);

    expect($documents)->toHaveCount(2);

    $this->tenant->run(function () use ($documents) {
        $document = $documents->first()->fresh();

        expect($document->documentable_id)->toBe($this->applicant->id)
            ->and($document->title)->toBe('RG/CNH')
            ->and($document->path)->toStartWith('proposals/'.$this->proposal->id.'/');

        Storage::disk('public')->assertExists($document->path);
    });
});

test('documento de comprador exige um proponente da própria proposta', function () {
    $outsider = $this->tenant->run(fn () => Applicant::factory()->create());

    $this->service->store((string) $this->proposal->id, [
        'owner' => DocumentOwner::BUYER->value,
        'person_id' => $outsider->id,
    ], [UploadedFile::fake()->create('rg.pdf', 20)], $this->user, $this->tenant);
})->throws(ValidationException::class);

test('excluir remove o registro e o arquivo', function () {
    $document = $this->service->store((string) $this->proposal->id, [
        'owner' => DocumentOwner::PROPERTY->value,
        'type' => DocumentType::IPTU_COVER->value,
    ], [UploadedFile::fake()->create('iptu.pdf', 20)], $this->user, $this->tenant)->first();

    $this->service->delete((string) $this->proposal->id, (string) $document->id, $this->tenant);

    $this->tenant->run(fn () => expect(ProposalDocument::find($document->id))->toBeNull());
    Storage::disk('public')->assertMissing($document->path);
});

test('storeGenerated anexa um PDF gerado como documento geral', function () {
    $this->tenant->run(fn () => $this->service->storeGenerated($this->proposal, '%PDF-1.4 conteúdo', DocumentType::FEE_ESTIMATE, 'Orçamento 00001', $this->tenant));

    $this->tenant->run(function () {
        $document = $this->proposal->documents()->firstOrFail();

        expect($document->owner)->toBe(DocumentOwner::GENERAL)
            ->and($document->type)->toBe(DocumentType::FEE_ESTIMATE)
            ->and($document->mime_type)->toBe('application/pdf');

        Storage::disk('public')->assertExists($document->path);
    });
});

test('o checklist marca os obrigatórios enviados por pessoa', function () {
    $this->service->store((string) $this->proposal->id, [
        'owner' => DocumentOwner::BUYER->value,
        'person_id' => $this->applicant->id,
        'type' => DocumentType::ID_DOCUMENT->value,
    ], [UploadedFile::fake()->create('rg.pdf', 20)], $this->user, $this->tenant);

    $checklist = $this->tenant->run(fn () => $this->service->checklist($this->proposal->fresh()));
    $buyer = collect($checklist)->firstWhere('owner', DocumentOwner::BUYER->value);
    $idDocument = collect($buyer['required'])->firstWhere('value', DocumentType::ID_DOCUMENT->value);

    expect($idDocument['sent'])->toBeTrue()
        ->and($buyer['complete'])->toBeFalse()
        ->and(collect($checklist)->pluck('owner')->all())->toBe([
            DocumentOwner::BUYER->value,
            DocumentOwner::SELLER->value,
            DocumentOwner::PROPERTY->value,
        ]);
});

test('a falta de documentos do vendedor é comparada por conjunto', function () {
    foreach (DocumentType::requiredFor(DocumentOwner::SELLER) as $type) {
        $this->service->store((string) $this->proposal->id, [
            'owner' => DocumentOwner::SELLER->value,
            'person_id' => $this->seller->id,
            'type' => $type->value,
        ], [UploadedFile::fake()->create($type->value.'.pdf', 5)], $this->user, $this->tenant);
    }

    $missing = $this->tenant->run(fn () => $this->service->missingSellerDocuments($this->proposal->fresh()));

    expect($missing)->toBe([]);
});
