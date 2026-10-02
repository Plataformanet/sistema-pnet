<?php

use App\Enums\DocumentOwner;
use App\Enums\ProposalStatus;
use App\Exceptions\ProposalTimelineException;
use App\Http\Requests\CompleteProposalStageRequest;
use App\Mail\ProposalStageCompletedMail;
use App\Mail\ProposalStagesFinishedMail;
use App\Mail\ProposalTrackingStartedMail;
use App\Models\Applicant;
use App\Models\ProposalStage;
use App\Models\Stage;
use App\Models\User;
use App\Services\ProposalTimelineService;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->tenant = sharedTenant();
    Mail::fake();
    config(['bucket.disk' => 'public']);
    Storage::fake('public');

    $this->service = app(ProposalTimelineService::class);
    $this->actor = $this->tenant->run(fn () => User::factory()->create());

    $this->proposal = createProposal($this->tenant);
    $this->tenant->run(fn () => $this->proposal->applicants()->attach(Applicant::factory()->create()));
    $this->service->instantiate($this->proposal, $this->tenant);
});

/**
 * @return Collection<int, ProposalStage>
 */
function timelineStages()
{
    return test()->tenant->run(fn () => test()->proposal->stages()->with('stage')->get());
}

test('instantiate cria uma etapa por etapa ativa, na ordem, com a primeira atual', function () {
    $stages = timelineStages();

    $this->tenant->run(function () use ($stages) {
        expect($stages)->toHaveCount(Stage::count())
            ->and($stages->pluck('position')->all())->toBe(Stage::ordered()->pluck('order')->all())
            ->and($stages->where('is_current', true))->toHaveCount(1)
            ->and($stages->first()->is_current)->toBeTrue();
    });
});

test('etapas excluídas não entram em novas timelines', function () {
    $this->tenant->run(fn () => Stage::where('order', 4)->firstOrFail()->delete());
    $proposal = createProposal($this->tenant);

    $this->service->instantiate($proposal, $this->tenant);

    $this->tenant->run(fn () => expect($proposal->stages()->count())->toBe(Stage::count()));
});

test('iniciar o acompanhamento inicia a etapa atual e muda o status para em andamento', function () {
    $this->service->start((string) $this->proposal->id, $this->tenant);

    $this->tenant->run(function () {
        expect($this->proposal->fresh()->status)->toBe(ProposalStatus::IN_PROGRESS)
            ->and($this->proposal->currentStage()->first()->started_at)->not->toBeNull();
    });

    Mail::assertQueued(ProposalTrackingStartedMail::class);
});

test('iniciar duas vezes é bloqueado', function () {
    $this->service->start((string) $this->proposal->id, $this->tenant);
    $this->service->start((string) $this->proposal->id, $this->tenant);
})->throws(ProposalTimelineException::class);

test('não é possível concluir etapa antes de iniciar o acompanhamento', function () {
    $current = timelineStages()->first();

    $this->service->complete((string) $this->proposal->id, (string) $current->id, [], null, $this->actor, $this->tenant);
})->throws(ProposalTimelineException::class);

test('concluir a etapa avança para a próxima pela posição, não pelo id', function () {
    $this->tenant->run(function () {
        $stages = $this->proposal->stages()->get();
        $stages[1]->update(['position' => 99]);
    });

    $this->service->start((string) $this->proposal->id, $this->tenant);
    $first = timelineStages()->first();

    $this->service->complete((string) $this->proposal->id, (string) $first->id, ['notes' => 'ok'], null, $this->actor, $this->tenant);

    $this->tenant->run(function () use ($first) {
        $current = $this->proposal->currentStage()->first();
        $expected = $this->proposal->stages()->where('id', '!=', $first->id)->orderBy('position')->first();

        expect($first->fresh()->completed_at)->not->toBeNull()
            ->and($first->fresh()->is_current)->toBeFalse()
            ->and($current->id)->toBe($expected->id)
            ->and($current->started_at)->not->toBeNull();
    });

    Mail::assertQueued(ProposalStageCompletedMail::class);
});

test('concluir com arquivo grava o documento da etapa no disco privado', function () {
    $this->service->start((string) $this->proposal->id, $this->tenant);
    $first = timelineStages()->first();

    $this->service->complete(
        (string) $this->proposal->id,
        (string) $first->id,
        ['title' => 'Aprovação'],
        UploadedFile::fake()->create('aprovacao.pdf', 10, 'application/pdf'),
        $this->actor,
        $this->tenant,
    );

    $this->tenant->run(function () use ($first) {
        $document = $first->fresh()->document;

        expect($document->owner)->toBe(DocumentOwner::STAGE)
            ->and($document->title)->toBe('Aprovação');

        Storage::disk('public')->assertExists($document->path);
    });
});

test('editar a etapa concluída só com o título renomeia o documento sem trocar o arquivo', function () {
    $this->service->start((string) $this->proposal->id, $this->tenant);
    $first = timelineStages()->first();
    $complete = fn (array $data, ?UploadedFile $file) => $this->service->complete(
        (string) $this->proposal->id,
        (string) $first->id,
        $data,
        $file,
        $this->actor,
        $this->tenant,
    );

    $complete(['title' => 'Aprovação'], UploadedFile::fake()->create('aprovacao.pdf', 10, 'application/pdf'));
    $original = $this->tenant->run(fn () => $first->fresh()->document);

    $complete(['title' => 'Aprovação final'], null);

    $this->tenant->run(function () use ($first, $original) {
        $document = $first->fresh()->document;

        expect($document->id)->toBe($original->id)
            ->and($document->path)->toBe($original->path)
            ->and($document->title)->toBe('Aprovação final');
    });
});

test('concluir a última etapa finaliza a proposta', function () {
    $this->service->start((string) $this->proposal->id, $this->tenant);

    foreach (timelineStages() as $proposalStage) {
        $this->service->complete((string) $this->proposal->id, (string) $proposalStage->id, ['date' => '2026-10-01'], null, $this->actor, $this->tenant);
    }

    $this->tenant->run(function () {
        $proposal = $this->proposal->fresh();

        expect($proposal->status)->toBe(ProposalStatus::FINISHED)
            ->and($proposal->finished_at)->not->toBeNull()
            ->and($proposal->currentStage()->first())->toBeNull();
    });

    Mail::assertQueued(ProposalStagesFinishedMail::class);
});

test('restaurar o acompanhamento recria a timeline, apaga documentos de etapa e volta ao estado não iniciado', function () {
    $this->service->start((string) $this->proposal->id, $this->tenant);
    $first = timelineStages()->first();
    $this->service->complete(
        (string) $this->proposal->id,
        (string) $first->id,
        [],
        UploadedFile::fake()->create('etapa.pdf', 10, 'application/pdf'),
        $this->actor,
        $this->tenant,
    );
    $path = $this->tenant->run(fn () => $first->fresh()->document->path);

    $this->service->restore((string) $this->proposal->id, $this->tenant);

    $this->tenant->run(function () {
        $stages = $this->proposal->stages()->get();

        expect($stages->whereNotNull('completed_at'))->toBeEmpty()
            ->and($stages->whereNotNull('started_at'))->toBeEmpty()
            ->and($stages->first()->is_current)->toBeTrue()
            ->and($this->proposal->fresh()->status)->toBe(ProposalStatus::NEW)
            ->and($this->proposal->documents()->where('owner', DocumentOwner::STAGE->value)->count())->toBe(0);
    });

    Storage::disk('public')->assertMissing($path);
});

test('restaurar uma proposta finalizada limpa a data de finalização e permite iniciar de novo', function () {
    $this->tenant->run(fn () => $this->proposal->update(['status' => ProposalStatus::FINISHED, 'finished_at' => now()]));

    $this->service->restore((string) $this->proposal->id, $this->tenant);
    $this->service->start((string) $this->proposal->id, $this->tenant);

    $this->tenant->run(function () {
        $proposal = $this->proposal->fresh();

        expect($proposal->status)->toBe(ProposalStatus::IN_PROGRESS)
            ->and($proposal->finished_at)->toBeNull()
            ->and($proposal->currentStage()->first()->started_at)->not->toBeNull();
    });
});

test('restaurar não altera o status de proposta cancelada', function () {
    $this->tenant->run(fn () => $this->proposal->update(['status' => ProposalStatus::CANCELED]));

    $this->service->restore((string) $this->proposal->id, $this->tenant);

    expect($this->tenant->run(fn () => $this->proposal->fresh()->status))->toBe(ProposalStatus::CANCELED);
});

test('overdueStages lista etapas em andamento que atingiram o prazo de alerta', function () {
    $this->service->start((string) $this->proposal->id, $this->tenant);

    $this->tenant->run(function () {
        $current = $this->proposal->currentStage()->with('stage')->first();
        $current->stage->update(['alert_deadline_hours' => 24, 'completion_deadline_hours' => 48]);
        $current->update(['started_at' => now()->subHours(30)]);
    });

    expect($this->service->overdueStages($this->tenant)->pluck('proposal_id'))->toContain($this->proposal->id);
});

test('a conclusão exige os campos obrigatórios configurados na etapa', function () {
    $current = $this->tenant->run(function () {
        $current = $this->proposal->currentStage()->with('stage')->first();
        $current->stage->update(['has_date' => true, 'date_required' => true, 'has_upload' => true, 'upload_required' => true, 'notes_required' => true]);

        return $current;
    });

    $request = CompleteProposalStageRequest::create('/', 'POST', []);
    $request->setContainer(app())->setRedirector(app(Redirector::class));
    $route = new Route('POST', 'documents/proposals/{id}/timeline/{proposalStageId}/complete', fn () => null);
    $route->bind($request);
    $route->setParameter('id', (string) $this->proposal->id);
    $route->setParameter('proposalStageId', (string) $current->id);
    $request->setRouteResolver(fn () => $route);

    try {
        $request->validateResolved();
        $errors = [];
    } catch (ValidationException $exception) {
        $errors = $exception->errors();
    }

    expect($errors)->toHaveKeys(['date', 'file', 'notes']);
});

test('se a conclusão falhar depois do upload, o arquivo enviado não fica órfão no disco', function () {
    $this->service->start((string) $this->proposal->id, $this->tenant);
    $first = timelineStages()->first();

    $this->tenant->run(fn () => ProposalStage::saving(fn () => throw new RuntimeException('falha simulada')));

    expect(fn () => $this->service->complete(
        (string) $this->proposal->id,
        (string) $first->id,
        ['title' => 'Aprovação'],
        UploadedFile::fake()->create('aprovacao.pdf', 10, 'application/pdf'),
        $this->actor,
        $this->tenant,
    ))->toThrow(RuntimeException::class, 'falha simulada');

    $this->tenant->run(function () use ($first) {
        expect(Storage::disk('public')->allFiles())->toBeEmpty()
            ->and($first->fresh()->proposal_document_id)->toBeNull();
    });
});
