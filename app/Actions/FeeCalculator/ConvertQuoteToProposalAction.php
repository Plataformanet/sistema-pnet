<?php

namespace App\Actions\FeeCalculator;

use App\Enums\QuoteStatusEnum;
use App\Exceptions\QuoteNotOpenException;
use App\Jobs\AttachQuotePdfToProposalJob;
use App\Models\Proposal;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FeeCalculator\CostItemsBuilder;
use App\Services\ProposalService;
use App\Services\RegistryFee\RegistryFeeResultDto;
use Illuminate\Support\Facades\DB;

/**
 * Converte um orçamento em aberto em proposta. Idempotente: o orçamento é
 * travado e a checagem de status acontece dentro da transação, então uma
 * segunda chamada não cria outra proposta. PDF e e-mail só após o commit.
 */
class ConvertQuoteToProposalAction
{
    public function __construct(
        protected ProposalService $proposalService,
        protected CostItemsBuilder $costItemsBuilder,
    ) {}

    /**
     * @throws QuoteNotOpenException quando o orçamento expirou ou já foi convertido.
     */
    public function handle(string $quoteId, User $actor, Tenant $tenant): Proposal
    {
        return $tenant->run(function () use ($quoteId, $actor, $tenant) {
            return DB::transaction(function () use ($quoteId, $actor, $tenant) {
                $quote = Quote::with(['feeEstimate', 'services'])->lockForUpdate()->findOrFail($quoteId);

                match ($quote->status()) {
                    QuoteStatusEnum::CONVERTED => throw new QuoteNotOpenException('Já foi gerada uma proposta para este orçamento.'),
                    QuoteStatusEnum::EXPIRED => throw new QuoteNotOpenException('Orçamento com data de validade expirada: será necessário gerar um novo.'),
                    QuoteStatusEnum::OPEN => null,
                };

                $proposal = $this->proposalService->createFromQuote($quote, $actor, $tenant);
                $estimate = $quote->feeEstimate;

                $selection = $quote->services->map(fn ($service) => ['service' => $service, 'amount' => (int) $service->pivot->amount]);

                $proposal->costItems()->createMany($this->costItemsBuilder->build(
                    RegistryFeeResultDto::fromArray($estimate->api_result),
                    $estimate->type,
                    $estimate->itbi_amount,
                    $selection,
                ));

                $proposal->billableServices()->sync(
                    $selection->mapWithKeys(fn (array $item) => [$item['service']->id => ['amount' => $item['amount']]])->all()
                );

                $estimate->update(['proposal_id' => $proposal->id]);

                AttachQuotePdfToProposalJob::dispatch($quote->id, $proposal->id, $actor->id)->afterCommit();

                $this->proposalService->notifyApplicant($proposal->applicants->first(), $proposal, $tenant);

                return $proposal;
            });
        });
    }
}
