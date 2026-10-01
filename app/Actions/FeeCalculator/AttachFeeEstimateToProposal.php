<?php

namespace App\Actions\FeeCalculator;

use App\Exceptions\ProposalAlreadyHasFeeEstimateException;
use App\Models\FeeCalculation;
use App\Models\FeeEstimate;
use App\Models\Proposal;
use App\Models\Tenant;
use App\Services\BillableServiceService;
use App\Services\FeeCalculator\CostItemsBuilder;
use App\Services\RegistryFee\RegistryFeeResult;
use Illuminate\Support\Facades\DB;

/**
 * Vincula um cálculo a uma proposta existente: congela o resultado em
 * `fee_estimates` (sem validade), gera as linhas de custo e anexa os serviços.
 */
class AttachFeeEstimateToProposal
{
    public function __construct(
        protected CostItemsBuilder $costItemsBuilder,
        protected BillableServiceService $billableServiceService,
    ) {}

    /**
     * @param  array<int, array{id: int|string, amount?: int|null}>  $services
     *
     * @throws ProposalAlreadyHasFeeEstimateException quando a proposta já tem emolumento vinculado.
     */
    public function handle(FeeCalculation $calculation, string $proposalId, array $services, Tenant $tenant): Proposal
    {
        return $tenant->run(function () use ($calculation, $proposalId, $services, $tenant) {
            return DB::transaction(function () use ($calculation, $proposalId, $services, $tenant) {
                $proposal = Proposal::lockForUpdate()->findOrFail($proposalId);

                if ($proposal->feeEstimate()->withTrashed()->exists()) {
                    throw new ProposalAlreadyHasFeeEstimateException;
                }

                $selection = $this->billableServiceService->resolveSelection($services, $tenant);

                FeeEstimate::create(array_merge(FeeEstimate::attributesFrom($calculation), [
                    'proposal_id' => $proposal->id,
                    'valid_until' => null,
                ]));

                $proposal->costItems()->createMany($this->costItemsBuilder->build(
                    RegistryFeeResult::fromArray($calculation->api_result),
                    $calculation->type,
                    $calculation->itbi_amount,
                    $selection,
                ));

                $proposal->billableServices()->syncWithoutDetaching(
                    $selection->mapWithKeys(fn (array $item) => [$item['service']->id => ['amount' => $item['amount']]])->all()
                );

                return $proposal;
            });
        });
    }
}
