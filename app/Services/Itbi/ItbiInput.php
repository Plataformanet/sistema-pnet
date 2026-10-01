<?php

namespace App\Services\Itbi;

use App\Enums\CalculationType;
use App\Enums\FinancingSystem;
use App\Models\ItbiMunicipality;

/**
 * Entrada do cálculo de ITBI. Valores em centavos; o município deve vir com
 * `rate` e `brackets` carregados.
 */
final readonly class ItbiInput
{
    public function __construct(
        public CalculationType $type,
        public int $propertyValue,
        public ?ItbiMunicipality $municipality,
        public FinancingSystem $financingSystem = FinancingSystem::SFH,
        public int $financedValue = 0,
        public bool $firstProperty = false,
    ) {}
}
