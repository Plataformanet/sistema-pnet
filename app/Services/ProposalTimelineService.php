<?php

namespace App\Services;

use App\Enums\DocumentOwner;
use App\Enums\ProposalStatus;
use App\Exceptions\ProposalTimelineException;
use App\Mail\MissingSellerDocumentsMail;
use App\Mail\ProposalStageCompletedMail;
use App\Mail\ProposalStagesFinishedMail;
use App\Mail\ProposalTrackingStartedMail;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\ProposalStage;
use App\Models\Stage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Acompanhamento da proposta por etapas. As etapas são copiadas do catálogo
 * na criação da timeline (`position` = `order` daquele momento); reordenar o
 * catálogo não mexe em timelines existentes.
 */
class ProposalTimelineService
{
    public function __construct(
        protected ProposalDocumentService $proposalDocumentService,
    ) {}

    /**
     * Uma etapa por etapa ativa do catálogo, na ordem do catálogo; a primeira
     * fica como atual e nenhuma começa iniciada.
     */
    public function instantiate(Proposal $proposal, Tenant $tenant): void
    {
        $tenant->run(function () use ($proposal) {
            $stages = Stage::ordered()->get(['id', 'order']);

            $stages->values()->each(fn (Stage $stage, int $index) => $proposal->stages()->create([
                'stage_id' => $stage->id,
                'position' => $stage->order,
                'is_current' => $index === 0,
            ]));
        });
    }

    /**
     * @throws ProposalTimelineException quando a timeline já foi iniciada ou não tem etapas.
     */
    public function start(string $proposalId, Tenant $tenant): void
    {
        $tenant->run(function () use ($proposalId) {
            DB::transaction(function () use ($proposalId) {
                $proposal = Proposal::lockForUpdate()->findOrFail($proposalId);
                $current = $proposal->currentStage()->first();

                if ($current === null) {
                    throw new ProposalTimelineException('Esta proposta não possui etapas para acompanhar.');
                }

                if ($current->started_at !== null) {
                    throw new ProposalTimelineException('O acompanhamento desta proposta já foi iniciado.');
                }

                $current->update(['started_at' => now()]);

                if ($proposal->status === ProposalStatus::NEW) {
                    $proposal->update(['status' => ProposalStatus::IN_PROGRESS]);
                }

                $this->notifyApplicants($proposal, fn (string $name) => new ProposalTrackingStartedMail($name, $proposal->number), bccCreator: true);
            });
        });
    }

    /**
     * Conclui a etapa atual (ou atualiza uma etapa já concluída). As regras de
     * obrigatoriedade vêm do catálogo da etapa e são validadas no Form Request.
     * Concluir a última etapa finaliza a proposta.
     *
     * @param  array{date?: string|null, notes?: string|null, title?: string|null}  $data
     *
     * @throws ProposalTimelineException quando a etapa não está em andamento nem concluída.
     */
    public function complete(string $proposalId, string $proposalStageId, array $data, ?UploadedFile $file, User $actor, Tenant $tenant): void
    {
        $storedPath = null;

        try {
            $replacedPath = $this->completeInTransaction($proposalId, $proposalStageId, $data, $file, $actor, $tenant, $storedPath);
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                $tenant->run(fn () => $this->proposalDocumentService->disk()->delete($storedPath));
            }

            throw $exception;
        }

        if ($replacedPath !== null) {
            $tenant->run(fn () => $this->proposalDocumentService->disk()->delete($replacedPath));
        }
    }

    /**
     * Parte transacional de `complete`. O arquivo enviado é gravado no disco
     * antes do commit; `$storedPath` devolve o caminho dele para que
     * `complete` o apague se a transação for desfeita.
     *
     * @param  array{date?: string|null, notes?: string|null, title?: string|null}  $data
     * @return string|null caminho do arquivo substituído, a apagar após o commit
     */
    private function completeInTransaction(string $proposalId, string $proposalStageId, array $data, ?UploadedFile $file, User $actor, Tenant $tenant, ?string &$storedPath): ?string
    {
        return $tenant->run(function () use ($proposalId, $proposalStageId, $data, $file, $actor, &$storedPath) {
            return DB::transaction(function () use ($proposalId, $proposalStageId, $data, $file, $actor, &$storedPath) {
                $proposal = Proposal::lockForUpdate()->findOrFail($proposalId);
                $proposalStage = $proposal->stages()->with('stage', 'document')->findOrFail($proposalStageId);
                $alreadyCompleted = $proposalStage->completed_at !== null;

                if (! $alreadyCompleted && ! ($proposalStage->is_current && $proposalStage->started_at !== null)) {
                    throw new ProposalTimelineException('Só é possível concluir a etapa atual, depois de iniciado o acompanhamento.');
                }

                $replaced = null;

                if ($file !== null) {
                    $replaced = $proposalStage->document;
                    $document = $this->proposalDocumentService->storeFile($proposal, $file, [
                        'uploaded_by' => $actor->id,
                        'owner' => DocumentOwner::STAGE,
                        'proposal_stage_id' => $proposalStage->id,
                        'title' => $data['title'] ?? $proposalStage->stage->name,
                    ]);
                    $storedPath = $document->path;
                    $proposalStage->proposal_document_id = $document->id;
                    $replaced?->delete();
                } elseif (filled($data['title'] ?? null) && $proposalStage->document !== null) {
                    $proposalStage->document->update(['title' => $data['title']]);
                }

                $proposalStage->fill([
                    'date' => $data['date'] ?? $proposalStage->date,
                    'notes' => $data['notes'] ?? $proposalStage->notes,
                ]);

                if ($alreadyCompleted) {
                    $proposalStage->save();

                    return $replaced?->path;
                }

                $proposalStage->fill(['completed_at' => now(), 'is_current' => false])->save();

                $next = $proposal->stages()->where('position', '>', $proposalStage->position)->orderBy('position')->first();

                if ($next !== null) {
                    $next->update(['is_current' => true, 'started_at' => now()]);
                    $this->notifyApplicants($proposal, fn (string $name) => new ProposalStageCompletedMail($name, $proposal->number, $proposalStage->stage->name));
                } else {
                    $this->finish($proposal);
                }

                return $replaced?->path;
            });
        });
    }

    /**
     * Apaga a timeline e os documentos de etapa (arquivos só após o commit) e
     * recria a timeline a partir do catálogo, sem iniciar nenhuma etapa: a
     * proposta volta ao estado anterior ao "Iniciar acompanhamento".
     */
    public function restore(string $proposalId, Tenant $tenant): void
    {
        $tenant->run(function () use ($proposalId, $tenant) {
            $documents = DB::transaction(function () use ($proposalId, $tenant) {
                $proposal = Proposal::lockForUpdate()->findOrFail($proposalId);
                $documents = $proposal->documents()->where('owner', DocumentOwner::STAGE->value)->get(['id', 'disk', 'path']);

                $proposal->stages()->delete();
                ProposalDocument::whereKey($documents->modelKeys())->delete();

                $this->instantiate($proposal, $tenant);

                if ($proposal->status === ProposalStatus::FINISHED || $proposal->status === ProposalStatus::IN_PROGRESS) {
                    $proposal->update(['status' => ProposalStatus::NEW, 'finished_at' => null]);
                }

                return $documents;
            });

            $documents->each(fn (ProposalDocument $document) => Storage::disk($document->disk)->delete($document->path));
        });
    }

    /**
     * Etapas em andamento cujo prazo de alerta (> 0) já foi atingido.
     *
     * @return Collection<int, ProposalStage>
     */
    public function overdueStages(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => ProposalStage::query()
            ->inProgress()
            ->whereHas('stage', fn ($query) => $query->where('alert_deadline_hours', '>', 0))
            ->whereHas('proposal')
            ->with(['stage:id,name,alert_deadline_hours', 'proposal.creator:id,name,email', 'proposal.applicants.contact:id,name_corporatereason,email'])
            ->get()
            ->filter(fn (ProposalStage $proposalStage) => $proposalStage->started_at->diffInHours(now()) >= $proposalStage->stage->alert_deadline_hours)
            ->values());
    }

    private function finish(Proposal $proposal): void
    {
        $proposal->update(['status' => ProposalStatus::FINISHED, 'finished_at' => now()]);

        $this->notifyApplicants($proposal, fn (string $name) => new ProposalStagesFinishedMail($name, $proposal->number));

        $missing = $this->proposalDocumentService->missingSellerDocuments($proposal);
        $creator = $proposal->creator;

        if ($missing !== [] && $creator !== null) {
            Mail::to($creator->email)->queue(new MissingSellerDocumentsMail($creator->name, $proposal->number, $missing));
        }
    }

    /**
     * @param  callable(string): Mailable  $makeMail
     */
    private function notifyApplicants(Proposal $proposal, callable $makeMail, bool $bccCreator = false): void
    {
        $proposal->loadMissing('applicants.contact', 'creator');

        foreach ($proposal->applicants as $applicant) {
            $pending = Mail::to($applicant->contact->email);

            if ($bccCreator && $proposal->creator !== null) {
                $pending->bcc($proposal->creator->email);
            }

            $pending->queue($makeMail($applicant->contact->name_corporatereason));
        }
    }
}
