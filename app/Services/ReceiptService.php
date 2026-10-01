<?php

namespace App\Services;

use App\Enums\CostItemType;
use App\Enums\ReceiptType;
use App\Exceptions\ReceiptNotAllowedException;
use App\Models\Proposal;
use App\Models\ProposalCostItem;
use App\Models\Receipt;
use App\Models\Tenant;
use Illuminate\Support\Arr;

class ReceiptService
{
    /**
     * Recibo geral (sem cobrança) ou vinculado a uma cobrança que gera recibo.
     * O tipo do recibo vinculado vem do tipo de custo da linha (assessoria ou
     * motoboy); serviços que geram recibo emitem recibo de assessoria. O nome
     * do cartório é gravado como texto, retrato do momento da emissão.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ReceiptNotAllowedException quando a cobrança não gera recibo.
     */
    public function store(string $proposalId, array $data, Tenant $tenant): Receipt
    {
        return $tenant->run(function () use ($proposalId, $data) {
            $proposal = Proposal::findOrFail($proposalId);
            $attributes = Arr::only($data, ['name', 'document', 'registration_number', 'total_spent', 'amount_deposited', 'date']);
            $attributes['document'] = preg_replace('/\D/', '', (string) $attributes['document']);

            if (empty($data['proposal_cost_item_id'])) {
                return $proposal->receipts()->create(array_merge($attributes, ['type' => ReceiptType::GENERAL]));
            }

            /** @var ProposalCostItem $costItem */
            $costItem = $proposal->costItems()->with(['costType', 'notary'])->findOrFail($data['proposal_cost_item_id']);

            if (! $costItem->canGenerateReceipt()) {
                throw new ReceiptNotAllowedException;
            }

            $type = $costItem->type === CostItemType::MANUAL
                ? $costItem->costType->receipt_type
                : ReceiptType::ADVISORY;

            return $proposal->receipts()->create(array_merge($attributes, [
                'type' => $type,
                'proposal_cost_item_id' => $costItem->id,
                'notary_name' => $costItem->notary?->name,
                'total_spent' => $attributes['total_spent'] ?? $costItem->amount,
            ]));
        });
    }

    public function findById(string $proposalId, string $receiptId, Tenant $tenant): Receipt
    {
        return $tenant->run(fn () => Receipt::where('proposal_id', $proposalId)->findOrFail($receiptId));
    }

    public function delete(string $proposalId, string $receiptId, Tenant $tenant): bool
    {
        return $tenant->run(fn () => (bool) Receipt::where('proposal_id', $proposalId)->findOrFail($receiptId)->delete());
    }
}
