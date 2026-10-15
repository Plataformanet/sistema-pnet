<?php

use App\Enums\DocumentOwner;
use App\Enums\ProposalStatus;
use App\Enums\RolesEnum;
use App\Jobs\BuildFinishedProposalsZipJob;
use App\Mail\FinishedProposalsZipReadyMail;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\Stage;
use App\Models\Tenant;
use App\Services\Reports\ReportZipBuilder;
use App\Services\ReportService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->tenant = sharedTenant();
    config(['bucket.disk' => 'public']);
    Storage::fake('public');

    $this->builder = app(ReportZipBuilder::class);
    $this->admin = userWithRole($this->tenant, RolesEnum::ADMIN, ['documents.proposal_documents.view', 'documents.proposals.view']);
});

/**
 * Proposta finalizada com o documento da etapa final gravado (ou não) no disco.
 */
function zipProposal(Tenant $tenant, string $title = 'Contrato registrado', bool $storeFile = true): Proposal
{
    return $tenant->run(function () use ($title, $storeFile) {
        $proposal = Proposal::factory()->create(['status' => ProposalStatus::FINISHED, 'finished_at' => now()]);
        $stage = $proposal->stages()->create(['stage_id' => Stage::factory()->create()->id, 'position' => 1, 'completed_at' => now()]);
        $path = "proposals/{$proposal->id}/final.pdf";

        $document = $proposal->documents()->create([
            'owner' => DocumentOwner::STAGE,
            'proposal_stage_id' => $stage->id,
            'title' => $title,
            'disk' => 'public',
            'path' => $path,
            'original_name' => 'final.PDF',
            'mime_type' => 'application/pdf',
            'size' => 7,
        ]);
        $stage->update(['proposal_document_id' => $document->id]);

        if ($storeFile) {
            Storage::disk('public')->put($path, 'conteudo');
        }

        return $proposal;
    });
}

/**
 * @return array<int, string>
 */
function zipEntries(string $path): array
{
    $zip = new ZipArchive;
    $zip->open($path);

    $names = [];

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $names[] = $zip->getNameIndex($index);
    }

    $zip->close();

    return $names;
}

test('o ZIP traz o documento final de cada proposta com nome sem colisão', function () {
    $first = zipProposal($this->tenant);
    $second = zipProposal($this->tenant);

    $proposals = app(ReportService::class)->forDownload(['proposal_ids' => [$first->id, $second->id]], $this->admin, $this->tenant);
    $path = $this->tenant->run(fn () => $this->builder->build($proposals, $this->admin));

    expect(zipEntries($path))->toEqualCanonicalizing([
        "proposta-{$first->number}-contrato-registrado.pdf",
        "proposta-{$second->number}-contrato-registrado.pdf",
    ]);

    File::delete($path);
});

test('arquivo ausente no disco vai para o LEIA-ME sem abortar o lote', function () {
    $present = zipProposal($this->tenant);
    $missing = zipProposal($this->tenant, 'Matrícula', storeFile: false);

    $proposals = app(ReportService::class)->forDownload(['proposal_ids' => [$present->id, $missing->id]], $this->admin, $this->tenant);
    $path = $this->tenant->run(fn () => $this->builder->build($proposals, $this->admin));

    $zip = new ZipArchive;
    $zip->open($path);
    $readme = $zip->getFromName('LEIA-ME.txt');
    $zip->close();

    expect(zipEntries($path))->toContain("proposta-{$present->number}-contrato-registrado.pdf", 'LEIA-ME.txt')
        ->and($readme)->toContain("Proposta Nº {$missing->number}: Matrícula");

    File::delete($path);
});

test('o ZIP não inclui documento que o usuário não pode baixar', function () {
    $proposal = zipProposal($this->tenant);
    $withoutPermission = userWithRole($this->tenant, RolesEnum::MANAGER, ['documents.proposals.view']);

    $proposals = app(ReportService::class)->forDownload(['proposal_ids' => [$proposal->id]], $this->admin, $this->tenant);
    $path = $this->tenant->run(fn () => $this->builder->build($proposals, $withoutPermission));

    expect(zipEntries($path))->toBe(['LEIA-ME.txt']);

    File::delete($path);
});

test('nomes repetidos recebem sufixo numérico', function () {
    $proposal = zipProposal($this->tenant);

    $this->tenant->run(function () use ($proposal) {
        $document = ProposalDocument::where('proposal_id', $proposal->id)->firstOrFail();
        $used = [];

        expect($this->builder->entryName($proposal, $document, $used))->toBe("proposta-{$proposal->number}-contrato-registrado.pdf")
            ->and($this->builder->entryName($proposal, $document, $used))->toBe("proposta-{$proposal->number}-contrato-registrado-2.pdf");
    });
});

test('o job da fila grava o ZIP na pasta do usuário e envia o link por e-mail', function () {
    Mail::fake();
    $proposal = zipProposal($this->tenant);

    $this->tenant->run(fn () => (new BuildFinishedProposalsZipJob(
        $this->admin->id,
        [$proposal->id],
        'relatorio-propostas-finalizadas-2026-10-15-abcdefghijkl.zip',
        'https://empresa.localhost/documents/reports/downloads/arquivo.zip?signature=x',
    ))->handle(app(ReportService::class), $this->builder));

    $this->tenant->run(fn () => Storage::disk('public')->assertExists(
        BuildFinishedProposalsZipJob::storagePath($this->admin->id, 'relatorio-propostas-finalizadas-2026-10-15-abcdefghijkl.zip'),
    ));

    Mail::assertQueued(FinishedProposalsZipReadyMail::class, fn (FinishedProposalsZipReadyMail $mail) => $mail->hasTo($this->admin->email)
        && $mail->proposalCount === 1
        && str_contains($mail->downloadUrl, 'signature='));
});

test('a limpeza apaga só os ZIPs com o link expirado e os temporários abandonados', function () {
    config(['reports.zip_link_ttl_hours' => 24]);

    $this->tenant->run(function () {
        $disk = Storage::disk('public');
        $expired = BuildFinishedProposalsZipJob::storagePath($this->admin->id, 'antigo.zip');
        $recent = BuildFinishedProposalsZipJob::storagePath($this->admin->id, 'recente.zip');

        $disk->put($expired, 'zip');
        $disk->put($recent, 'zip');
        touch($disk->path($expired), now()->subHours(25)->getTimestamp());

        $temporaryDirectory = storage_path(ReportZipBuilder::TMP_PATH);
        File::ensureDirectoryExists($temporaryDirectory);
        $abandoned = $temporaryDirectory.'/abandonado.zip';
        $inUse = $temporaryDirectory.'/em-uso.zip';
        File::put($abandoned, 'zip');
        File::put($inUse, 'zip');
        touch($abandoned, now()->subHours(25)->getTimestamp());

        $deleted = app(ReportService::class)->pruneExpiredZips($this->tenant);

        expect($deleted)->toBe(2)
            ->and($disk->exists($expired))->toBeFalse()
            ->and($disk->exists($recent))->toBeTrue()
            ->and(File::exists($abandoned))->toBeFalse()
            ->and(File::exists($inUse))->toBeTrue();

        File::delete($inUse);
    });
});
