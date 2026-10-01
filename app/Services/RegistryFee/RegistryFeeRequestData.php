<?php

namespace App\Services\RegistryFee;

use App\Enums\CalculationType;
use App\Enums\FeeDiscount;
use App\Enums\SupportedState;

/**
 * Entrada da API de emolumentos. Valores em centavos.
 */
final readonly class RegistryFeeRequestData
{
    public function __construct(
        public CalculationType $type,
        public SupportedState $state,
        public int $municipalityIbgeCode,
        public int $propertyValue,
        public int $financingValue = 0,
        public ?FeeDiscount $discount = null,
    ) {}

    /**
     * A API recebe reais inteiros: os centavos são truncados (comportamento
     * da origem, a confirmar com o responsável pela integração). Esta é a
     * única conversão; o ITBI é calculado com os centavos.
     *
     * @return array<string, int|string>
     */
    public function toPayload(): array
    {
        $payload = [
            'codigo_municipio' => $this->municipalityIbgeCode,
            'consulta_id' => $this->type->value,
            'valor_imovel' => intdiv($this->propertyValue, 100),
            'valor_financiamento' => $this->type->requiresFinancing() ? intdiv($this->financingValue, 100) : 0,
        ];

        if ($this->discount !== null) {
            $payload['desconto'] = $this->discount->apiValue($this->state);
        }

        return $payload;
    }
}
