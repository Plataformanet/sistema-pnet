<?php

namespace App\Services\Itbi;

use App\Enums\CalculationTypeEnum;
use App\Enums\FinancingSystemEnum;
use App\Models\ItbiMunicipality;

/**
 * Entrada do cálculo de ITBI. Valores em centavos; o município deve vir com
 * `rate` e `brackets` carregados.
 */
final readonly class ItbiInputDto
{
    public function __construct(
        public CalculationTypeEnum $type,
        public int $propertyValue,
        public ?ItbiMunicipality $municipality,
        public FinancingSystemEnum $financingSystem = FinancingSystemEnum::SFH,
        public int $financedValue = 0,
        public bool $firstProperty = false,
    ) {}
}
