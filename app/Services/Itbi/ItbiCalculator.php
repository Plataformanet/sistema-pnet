<?php

namespace App\Services\Itbi;

use App\Enums\CalculationTypeEnum;
use App\Enums\FinancingSystemEnum;
use App\Enums\ItbiModuleEnum;
use App\Exceptions\Itbi\ItbiNotConfiguredException;
use App\Models\ItbiBracket;
use App\Models\ItbiMunicipality;

/**
 * Cálculo local do ITBI pelo módulo do município. Classe pura (sem HTTP nem
 * request): recebe e devolve centavos, arredondando só no fim da fórmula.
 *
 * Registro em geral e compra com SFI usam a alíquota cheia sobre o valor do
 * imóvel; compra com SFH aplica a alíquota do financiado sobre a parte
 * financiada. Averbação não tem ITBI.
 */
class ItbiCalculator
{
    /**
     * @throws ItbiNotConfiguredException quando o município não tem cadastro ou o valor está fora das faixas.
     */
    public function calculate(ItbiInputDto $input): ?int
    {
        if (! $input->type->hasItbi()) {
            return null;
        }

        $municipality = $input->municipality;

        if ($municipality === null || ($municipality->module !== ItbiModuleEnum::VALUE_BRACKETS && $municipality->rate === null)) {
            throw ItbiNotConfiguredException::municipality();
        }

        $value = $input->propertyValue;
        $financed = $input->financedValue;
        $fullRate = $input->type === CalculationTypeEnum::GENERAL_REGISTRATION || $input->financingSystem === FinancingSystemEnum::SFI;

        $result = match ($municipality->module) {
            ItbiModuleEnum::VALUE_BRACKETS => $fullRate
                ? $value * $this->percent($municipality->full_rate)
                : $this->bracketTax($municipality, $value),
            ItbiModuleEnum::FINANCED_CAP => $fullRate
                ? $value * $this->percent($municipality->rate->own_funds_rate)
                : $this->financedCapTax($municipality, $value, $financed),
            ItbiModuleEnum::FIRST_PROPERTY_RATE => $fullRate
                ? $value * $this->percent($municipality->rate->own_funds_rate)
                : ($value - $financed) * $this->percent($municipality->rate->own_funds_rate)
                    + $financed * $this->percent($input->firstProperty
                        ? $municipality->rate->first_property_financed_rate
                        : $municipality->rate->other_property_financed_rate),
            ItbiModuleEnum::STANDARD => $fullRate
                ? $value * $this->percent($municipality->rate->own_funds_rate)
                : ($value - $financed) * $this->percent($municipality->rate->own_funds_rate)
                    + $financed * $this->percent($municipality->rate->financed_rate),
        };

        return max(0, (int) round($result));
    }

    /**
     * Módulo 01: a alíquota do financiado vale só até o teto; o que passar do
     * teto paga a alíquota de recursos próprios.
     */
    private function financedCapTax(ItbiMunicipality $municipality, int $value, int $financed): float
    {
        $rate = $municipality->rate;
        $cap = (int) $rate->financed_cap_amount;

        if ($financed > $cap) {
            return ($value - $cap) * $this->percent($rate->own_funds_rate) + $cap * $this->percent($rate->financed_rate);
        }

        return ($value - $financed) * $this->percent($rate->own_funds_rate) + $financed * $this->percent($rate->financed_rate);
    }

    /**
     * Módulo 02 com SFH: faixa com limites inclusivos (min ≤ V ≤ max).
     */
    private function bracketTax(ItbiMunicipality $municipality, int $value): float
    {
        /** @var ItbiBracket|null $bracket */
        $bracket = $municipality->brackets->first(fn (ItbiBracket $bracket) => $bracket->contains($value));

        if ($bracket === null) {
            throw ItbiNotConfiguredException::outOfBrackets($municipality->name);
        }

        return $value * $this->percent($bracket->rate) - $bracket->discount_amount;
    }

    private function percent(float|string|null $rate): float
    {
        return ((float) $rate) / 100;
    }
}
