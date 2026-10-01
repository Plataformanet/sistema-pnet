<?php

namespace App\Jobs;

use App\Enums\DocumentType;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ProposalDocumentService;
use App\Services\QuotePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Gera o PDF do orçamento convertido e o anexa aos documentos da proposta
 * (tipo "Emolumento"). Despachado após o commit da conversão; a fila restaura
 * o tenant de origem (QueueTenancyBootstrapper).
 */
class AttachQuotePdfToProposal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $quoteId,
        public int $proposalId,
        public ?int $userId = null,
    ) {}

    public function handle(QuotePdfService $quotePdfService, ProposalDocumentService $proposalDocumentService): void
    {
        $tenant = tenant();
        $proposal = Proposal::findOrFail($this->proposalId);
        $viewer = $this->userId !== null ? User::find($this->userId) : null;

        $contents = $quotePdfService->make((string) $this->quoteId, $viewer, $tenant)->output();

        $proposalDocumentService->storeGenerated(
            $proposal,
            $contents,
            DocumentType::FEE_ESTIMATE,
            'Orçamento Nº '.str_pad((string) $this->quoteId, 5, '0', STR_PAD_LEFT),
            $tenant,
        );
    }
}
