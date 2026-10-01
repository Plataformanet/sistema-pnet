<?php

namespace App\Actions\FeeCalculator;

use App\Enums\QuoteStatus;
use App\Exceptions\QuoteNotOpenException;
use App\Models\Quote;
use App\Models\Tenant;
use App\Services\BillableServiceService;
use Illuminate\Support\Facades\DB;

/**
 * Edita cliente, serviços e validade do orçamento. Os valores do cálculo não
 * mudam: para isso gera-se outro cálculo. Orçamento convertido é bloqueado.
 */
class UpdateQuote
{
    public function __construct(
        protected BillableServiceService $billableServiceService,
    ) {}

    /**
     * @param  array<string, mixed>  $data  dados validados por UpdateQuoteRequest
     *
     * @throws QuoteNotOpenException quando o orçamento já foi convertido em proposta.
     */
    public function handle(string $quoteId, array $data, Tenant $tenant): Quote
    {
        return $tenant->run(function () use ($quoteId, $data, $tenant) {
            return DB::transaction(function () use ($quoteId, $data, $tenant) {
                $quote = Quote::with('feeEstimate')->lockForUpdate()->findOrFail($quoteId);

                if ($quote->status() === QuoteStatus::CONVERTED) {
                    throw new QuoteNotOpenException('Este orçamento já foi convertido em proposta e não pode ser editado.');
                }

                $quote->update(CreateQuote::clientAttributes($data));
                $quote->feeEstimate->update(['valid_until' => $data['valid_until']]);

                $quote->services()->sync(
                    $this->billableServiceService->resolveSelection($data['services'] ?? [], $tenant)
                        ->mapWithKeys(fn (array $item) => [$item['service']->id => ['amount' => $item['amount']]])
                        ->all()
                );

                return $quote;
            });
        });
    }
}
