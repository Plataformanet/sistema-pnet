<?php

namespace App\Enums;

/**
 * Módulo de cálculo do ITBI do município (módulos 01 a 04 da origem).
 */
enum ItbiModule: string
{
    case FINANCED_CAP = 'financed_cap';
    case VALUE_BRACKETS = 'value_brackets';
    case FIRST_PROPERTY_RATE = 'first_property_rate';
    case STANDARD = 'standard';

    public function label(): string
    {
        return match ($this) {
            self::FINANCED_CAP => 'Módulo 01 — teto do valor financiado',
            self::VALUE_BRACKETS => 'Módulo 02 — faixas de valor do imóvel',
            self::FIRST_PROPERTY_RATE => 'Módulo 03 — alíquota de primeiro imóvel',
            self::STANDARD => 'Módulo 04 — padrão',
        };
    }

    /**
     * Campos de `itbi_rates` usados pelo módulo (faixas usam `itbi_brackets`).
     *
     * @return array<int, string>
     */
    public function requiredFields(): array
    {
        return match ($this) {
            self::FINANCED_CAP => ['own_funds_rate', 'financed_rate', 'financed_cap_amount'],
            self::FIRST_PROPERTY_RATE => ['own_funds_rate', 'first_property_financed_rate', 'other_property_financed_rate'],
            self::STANDARD => ['own_funds_rate', 'financed_rate'],
            self::VALUE_BRACKETS => [],
        };
    }

    public function usesBrackets(): bool
    {
        return $this === self::VALUE_BRACKETS;
    }

    /**
     * @return array<int, array{value: string, label: string, fields: array<int, string>}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'fields' => $case->requiredFields(),
        ], self::cases());
    }
}
