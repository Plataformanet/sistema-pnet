<?php

namespace App\Enums;

/**
 * Tipo de cálculo da API de emolumentos (valor = `consulta_id` da API, o mesmo
 * da origem, o que facilita migrar dados).
 */
enum CalculationType: int
{
    case GENERAL_REGISTRATION = 1;
    case PURCHASE_WITH_FIDUCIARY_LIEN = 2;
    case ECONOMIC_VALUE_ANNOTATION = 3;

    public function label(): string
    {
        return match ($this) {
            self::GENERAL_REGISTRATION => 'Registro em geral',
            self::PURCHASE_WITH_FIDUCIARY_LIEN => 'Compra e venda com alienação fiduciária',
            self::ECONOMIC_VALUE_ANNOTATION => 'Averbação com valor econômico',
        };
    }

    public function description(): string
    {
        return __('fee_calculator.types.'.$this->value, [], 'pt_BR');
    }

    /**
     * A averbação não gera ITBI.
     */
    public function hasItbi(): bool
    {
        return $this !== self::ECONOMIC_VALUE_ANNOTATION;
    }

    public function requiresFinancing(): bool
    {
        return $this === self::PURCHASE_WITH_FIDUCIARY_LIEN;
    }

    /**
     * @param  array<int, self>|null  $cases
     * @return array<int, array{value: int, label: string, description: string}>
     */
    public static function options(?array $cases = null): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label(), 'description' => $type->description()],
            $cases ?? self::cases(),
        );
    }
}
