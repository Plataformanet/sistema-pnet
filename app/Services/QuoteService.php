<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Exceptions\QuoteNotOpenException;
use App\Models\Quote;
use App\Models\Tenant;
use App\Services\FeeCalculator\FeeBreakdown;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    public const PER_PAGE = 20;

    /**
     * @param  array{search?: string|null}  $filters
     */
    public function paginate(array $filters, Tenant $tenant): LengthAwarePaginator
    {
        return $tenant->run(function () use ($filters) {
            $search = $filters['search'] ?? null;
            $digits = $search !== null ? preg_replace('/\D/', '', $search) : '';

            return Quote::query()
                ->with('feeEstimate:id,quote_id,proposal_id,valid_until')
                ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search, $digits) {
                    $query->where('name', 'like', '%'.$search.'%');

                    if ($digits !== '') {
                        $query->orWhere('cpf', 'like', '%'.$digits.'%');
                    }
                }))
                ->latest('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString()
                ->through(fn (Quote $quote) => [
                    'id' => $quote->id,
                    'number' => $quote->number,
                    'name' => $quote->name,
                    'cpf' => $quote->cpf,
                    'valid_until' => $quote->feeEstimate?->valid_until?->format('Y-m-d'),
                    'status' => $this->statusPayload($quote->status()),
                ]);
        });
    }

    public function findForDisplay(string $id, Tenant $tenant): Quote
    {
        return $tenant->run(fn () => Quote::with(['feeEstimate', 'services', 'bank:id,name,deleted_at', 'creator:id,name'])->findOrFail($id));
    }

    public function breakdown(Quote $quote): FeeBreakdown
    {
        $estimate = $quote->feeEstimate;

        return FeeBreakdown::fromApiResult(
            $estimate->api_result,
            $estimate->itbi_amount,
            $quote->services->map(fn ($service) => ['name' => $service->name, 'amount' => (int) $service->pivot->amount])->values()->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Quote $quote): array
    {
        $estimate = $quote->feeEstimate;

        return [
            'id' => $quote->id,
            'number' => $quote->number,
            'name' => $quote->name,
            'cpf' => $quote->cpf,
            'email' => $quote->email,
            'phone' => $quote->phone,
            'profession' => $quote->profession,
            'marital_status' => $quote->marital_status->value,
            'marital_status_label' => $quote->marital_status->label(),
            'bank_id' => $quote->bank_id,
            'bank' => $quote->bank?->name,
            'creator' => $quote->creator?->name,
            'valid_until' => $estimate?->valid_until?->format('Y-m-d'),
            'proposal_id' => $estimate?->proposal_id,
            'calculation' => [
                'type' => $estimate?->type->label(),
                'state' => $estimate?->state->value,
                'municipality_name' => $estimate?->municipality_name,
            ],
            'services' => $quote->services->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'amount' => (int) $service->pivot->amount,
            ])->values()->all(),
            'status' => $this->statusPayload($quote->status()),
            'breakdown' => $this->breakdown($quote)->toArray(),
        ];
    }

    /**
     * Soft delete do orçamento e da estimativa. Orçamento convertido não pode
     * ser excluído: a estimativa dele pertence à proposta.
     *
     * @throws QuoteNotOpenException
     */
    public function delete(string $id, Tenant $tenant): void
    {
        $tenant->run(function () use ($id) {
            DB::transaction(function () use ($id) {
                $quote = Quote::with('feeEstimate')->lockForUpdate()->findOrFail($id);

                if ($quote->status() === QuoteStatus::CONVERTED) {
                    throw new QuoteNotOpenException('Orçamento convertido em proposta não pode ser excluído.');
                }

                $quote->feeEstimate?->delete();
                $quote->delete();
            });
        });
    }

    /**
     * @return array{value: string, label: string, variant: string}
     */
    private function statusPayload(QuoteStatus $status): array
    {
        return ['value' => $status->value, 'label' => $status->label(), 'variant' => $status->badgeVariant()];
    }
}
