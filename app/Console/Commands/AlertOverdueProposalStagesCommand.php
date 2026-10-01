<?php

namespace App\Console\Commands;

use App\Mail\ProposalStageOverdueMail;
use App\Models\ProposalStage;
use App\Models\Tenant;
use App\Services\ProposalTimelineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AlertOverdueProposalStagesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'proposals:alert-overdue-stages';

    /**
     * @var string
     */
    protected $description = 'Avisa criador e proponentes sobre etapas de propostas que atingiram o prazo de alerta';

    public function handle(ProposalTimelineService $proposalTimelineService): int
    {
        $sent = 0;

        Tenant::query()->each(function (Tenant $tenant) use ($proposalTimelineService, &$sent) {
            try {
                $tenant->run(function () use ($proposalTimelineService, $tenant, &$sent) {
                    $proposalTimelineService->overdueStages($tenant)->each(function (ProposalStage $proposalStage) use (&$sent) {
                        $proposal = $proposalStage->proposal;
                        $recipients = $proposal->applicants
                            ->map(fn ($applicant) => [$applicant->contact->email, $applicant->contact->name_corporatereason])
                            ->when($proposal->creator, fn ($recipients) => $recipients->push([$proposal->creator->email, $proposal->creator->name]))
                            ->unique(fn (array $recipient) => $recipient[0]);

                        $recipients->each(function (array $recipient) use ($proposal, $proposalStage, &$sent) {
                            Mail::to($recipient[0])->queue(new ProposalStageOverdueMail($recipient[1], $proposal->number, $proposalStage->stage->name));
                            $sent++;
                        });
                    });
                });
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Falha ao processar o tenant {$tenant->getTenantKey()}: {$exception->getMessage()}");
            }
        });

        $this->info("Alertas enviados: {$sent}");

        return self::SUCCESS;
    }
}
