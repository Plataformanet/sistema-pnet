<?php

use App\Enums\DocumentOwner;
use App\Enums\ProposalStatus;
use App\Enums\RolesEnum;
use App\Http\Requests\DownloadFinishedProposalsRequest;
use App\Http\Requests\FinishedProposalsReportRequest;
use App\Models\Applicant;
use App\Models\Contact;
use App\Models\Development;
use App\Models\PropertyType;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\Stage;
use App\Models\Tenant;
use App\Services\ReportService;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->tenant = sharedTenant();
    $this->service = app(ReportService::class);
    $this->admin = userWithRole($this->tenant, RolesEnum::ADMIN);
});

/**
 * Proposta finalizada com duas etapas; a final (maior posição) tem a data e,
 * opcionalmente, um documento.
 *
 * @param  array{final_date?: string|null, finished_at?: string, with_document?: bool, development?: Development|null, status?: ProposalStatus}  $options
 */
function finishedProposal(Tenant $tenant, array $options = []): Proposal
{
    return $tenant->run(function () use ($options) {
        $proposal = Proposal::factory()->create([
            'status' => $options['status'] ?? ProposalStatus::FINISHED,
            'finished_at' => $options['finished_at'] ?? '2026-10-05 15:00:00',
        ]);

        $proposal->stages()->create(['stage_id' => Stage::factory()->create()->id, 'position' => 1, 'date' => '2026-01-01', 'completed_at' => now()]);
        $finalStage = $proposal->stages()->create([
            'stage_id' => Stage::factory()->create()->id,
            'position' => 2,
            'date' => array_key_exists('final_date', $options) ? $options['final_date'] : '2026-10-01',
            'completed_at' => now(),
        ]);

        if ($options['with_document'] ?? true) {
            $document = $proposal->documents()->create([
                'owner' => DocumentOwner::STAGE,
                'proposal_stage_id' => $finalStage->id,
                'title' => 'Contrato registrado',
                'disk' => 'public',
                'path' => "proposals/{$proposal->id}/contrato.pdf",
                'original_name' => 'contrato.pdf',
                'mime_type' => 'application/pdf',
                'size' => 10,
            ]);
            $finalStage->update(['proposal_document_id' => $document->id]);
        }

        if (($options['development'] ?? null) !== null) {
            $proposal->property()->create([
                'property_type_id' => PropertyType::factory()->create()->id,
                'development_id' => $options['development']->id,
                'address' => 'Rua A',
            ]);
        }

        return $proposal;
    });
}

/**
 * @return array<int, int>
 */
function reportIds(ReportService $service, array $filters, $user, Tenant $tenant): array
{
    return collect($service->finishedProposals($filters, $user, $tenant)->items())->pluck('id')->all();
}

test('o relatório lista só propostas finalizadas e não excluídas', function () {
    $finished = finishedProposal($this->tenant);
    $inProgress = finishedProposal($this->tenant, ['status' => ProposalStatus::IN_PROGRESS]);
    $deleted = finishedProposal($this->tenant);
    $this->tenant->run(fn () => $deleted->delete());

    $ids = reportIds($this->service, [], $this->admin, $this->tenant);

    expect($ids)->toContain($finished->id)->not->toContain($inProgress->id)->not->toContain($deleted->id);
});

test('a data de finalização é a da etapa final e, sem ela, a de finished_at', function () {
    $withStageDate = finishedProposal($this->tenant, ['final_date' => '2026-09-20']);
    $withoutStageDate = finishedProposal($this->tenant, ['final_date' => null, 'finished_at' => '2026-09-25 10:00:00']);

    $rows = collect($this->service->finishedProposals([], $this->admin, $this->tenant)->items())->keyBy('id');

    expect($rows[$withStageDate->id]['report_date'])->toBe('2026-09-20')
        ->and($rows[$withoutStageDate->id]['report_date'])->toBe('2026-09-25');
});

test('o filtro de período usa a data de finalização e ordena da mais recente para a mais antiga', function () {
    $september = finishedProposal($this->tenant, ['final_date' => '2026-09-10']);
    $october = finishedProposal($this->tenant, ['final_date' => '2026-10-10']);
    $august = finishedProposal($this->tenant, ['final_date' => '2026-08-10']);

    $ids = reportIds($this->service, ['start_date' => '2026-09-01', 'end_date' => '2026-10-31'], $this->admin, $this->tenant);

    expect($ids)->toBe([$october->id, $september->id])->not->toContain($august->id);
});

test('o filtro por empreendimento usa o id e inclui empreendimento excluído', function () {
    [$development, $other] = $this->tenant->run(fn () => [Development::factory()->create(), Development::factory()->create()]);
    $inDevelopment = finishedProposal($this->tenant, ['development' => $development]);
    $inOther = finishedProposal($this->tenant, ['development' => $other]);
    $withoutProperty = finishedProposal($this->tenant);
    $this->tenant->run(fn () => $development->delete());

    $filtered = reportIds($this->service, ['development_id' => $development->id], $this->admin, $this->tenant);
    $unfiltered = reportIds($this->service, [], $this->admin, $this->tenant);

    expect($filtered)->toBe([$inDevelopment->id])
        ->and($unfiltered)->toContain($inDevelopment->id, $inOther->id, $withoutProperty->id);
});

test('a linha traz todos os proponentes, o empreendimento e o documento final', function () {
    $development = $this->tenant->run(fn () => Development::factory()->create(['name' => 'Residencial Sol']));
    $proposal = finishedProposal($this->tenant, ['development' => $development]);

    $this->tenant->run(fn () => $proposal->applicants()->attach([
        Applicant::factory()->create(['contact_id' => Contact::factory()->create(['name_corporatereason' => 'Ana'])])->id,
        Applicant::factory()->create(['contact_id' => Contact::factory()->create(['name_corporatereason' => 'Bruno'])])->id,
    ]));

    $row = collect($this->service->finishedProposals([], $this->admin, $this->tenant)->items())->firstWhere('id', $proposal->id);

    expect($row['applicants'])->toEqualCanonicalizing(['Ana', 'Bruno'])
        ->and($row['development'])->toBe('Residencial Sol')
        ->and($row['document']['title'])->toBe('Contrato registrado')
        ->and($row['number'])->toBe(str_pad((string) $proposal->id, 5, '0', STR_PAD_LEFT));
});

test('proposta finalizada sem documento na etapa final aparece sem documento', function () {
    $proposal = finishedProposal($this->tenant, ['with_document' => false]);

    $row = collect($this->service->finishedProposals([], $this->admin, $this->tenant)->items())->firstWhere('id', $proposal->id);

    expect($row)->not->toBeNull()
        ->and($row['document'])->toBeNull();
});

test('o total baixável do filtro conta só propostas com documento final', function () {
    finishedProposal($this->tenant);
    finishedProposal($this->tenant);
    finishedProposal($this->tenant, ['with_document' => false]);

    expect($this->service->downloadableCount([], $this->admin, $this->tenant))->toBe(2);
});

test('o download ignora ids fora do critério e propostas sem documento', function () {
    $finished = finishedProposal($this->tenant);
    $withoutDocument = finishedProposal($this->tenant, ['with_document' => false]);
    $inProgress = finishedProposal($this->tenant, ['status' => ProposalStatus::IN_PROGRESS]);

    $proposals = $this->service->forDownload([
        'proposal_ids' => [$finished->id, $withoutDocument->id, $inProgress->id, 999999],
    ], $this->admin, $this->tenant);

    expect($proposals->modelKeys())->toBe([$finished->id])
        ->and($proposals->first()->finalStage->document)->toBeInstanceOf(ProposalDocument::class);
});

test('o download de todos os resultados reaplica os filtros e respeita o limite', function () {
    $inPeriod = finishedProposal($this->tenant, ['final_date' => '2026-09-10']);
    finishedProposal($this->tenant, ['final_date' => '2026-09-11']);
    $outOfPeriod = finishedProposal($this->tenant, ['final_date' => '2026-01-10']);

    $all = $this->service->forDownload(
        ['all_matching' => true, 'start_date' => '2026-09-01', 'end_date' => '2026-09-30', 'proposal_ids' => [$outOfPeriod->id]],
        $this->admin,
        $this->tenant,
    );
    $limited = $this->service->forDownload(['all_matching' => true], $this->admin, $this->tenant, 1);

    expect($all->modelKeys())->toContain($inPeriod->id)->not->toContain($outOfPeriod->id)->toHaveCount(2)
        ->and($limited)->toHaveCount(1);
});

test('o filtro de datas exige as duas datas e início antes do fim', function () {
    $request = new FinishedProposalsReportRequest;

    $this->tenant->run(function () use ($request) {
        $inverted = Validator::make(['start_date' => '2026-10-10', 'end_date' => '2026-10-01'], $request->rules(), $request->messages());
        $missingEnd = Validator::make(['start_date' => '2026-10-10'], $request->rules(), $request->messages());

        expect($inverted->errors()->get('end_date'))->toBe(['A data final não pode ser anterior à data inicial.'])
            ->and($missingEnd->errors()->get('end_date'))->toBe(['Informe a data final do período.']);
    });
});

test('o pedido de ZIP exige ids ou a opção de todos os resultados, até o limite configurado', function () {
    config(['reports.zip_max_files' => 2]);
    $request = new DownloadFinishedProposalsRequest;

    $this->tenant->run(function () use ($request) {
        $empty = Validator::make([], $request->rules(), $request->messages());
        $tooMany = Validator::make(['proposal_ids' => [1, 2, 3]], $request->rules(), $request->messages());
        $allMatching = Validator::make(['all_matching' => true], $request->rules(), $request->messages());

        expect($empty->errors()->get('proposal_ids'))->toBe(['Selecione ao menos uma proposta.'])
            ->and($tooMany->errors()->get('proposal_ids'))->toBe(['Selecione no máximo 2 documentos.'])
            ->and($allMatching->passes())->toBeTrue();
    });
});
