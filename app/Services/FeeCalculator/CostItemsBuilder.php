<?php

namespace App\Services\FeeCalculator;

use App\Enums\CalculationType;
use App\Enums\CostItemType;
use App\Models\BillableService;
use App\Services\RegistryFee\RegistryFeeResult;
use Illuminate\Support\Str;

/**
 * Converte um cálculo (atos, taxas extras e ITBI) e os serviços escolhidos em
 * linhas de `proposal_cost_items`. Único ponto dessa regra, usado tanto ao
 * vincular o cálculo a uma proposta quanto na conversão do orçamento.
 */
class CostItemsBuilder
{
    /**
     * @param  iterable<int, array{service: BillableService, amount: int}>  $services  valor efetivo (negociado ou de tabela)
     * @return array<int, array<string, mixed>>
     */
    public function build(RegistryFeeResult $result, CalculationType $type, ?int $itbi, iterable $services = []): array
    {
        $items = [];

        foreach ($result->acts as $act) {
            $items[] = [
                'type' => CostItemType::EMOLUMENT,
                'description' => Str::limit((string) ($act['descricao'] ?? 'Emolumento'), 188),
                'amount' => (int) ($act['subtotal'] ?? 0),
            ];
        }

        foreach ($result->extraFees as $fee) {
            $items[] = [
                'type' => CostItemType::EXTRA_FEE,
                'extra_fee_description' => Str::limit($fee['description'], 188),
                'amount' => $fee['amount'],
            ];
        }

        foreach ($services as $item) {
            $items[] = [
                'type' => CostItemType::SERVICE,
                'description' => Str::limit($item['service']->name, 188),
                'service_description' => $item['service']->description,
                'generates_receipt' => $item['service']->generates_receipt,
                'amount' => $item['amount'],
            ];
        }

        if ($type->hasItbi() && $itbi !== null) {
            $items[] = [
                'type' => CostItemType::ITBI,
                'description' => 'ITBI',
                'amount' => $itbi,
            ];
        }

        return $items;
    }
}
