<?php

namespace App\Enums;

/**
 * UFs atendidas pela API de emolumentos (subconjunto de `BrazilianStateEnum`).
 */
enum SupportedStateEnum: string
{
    case AM = 'AM';
    case BA = 'BA';
    case ES = 'ES';
    case GO = 'GO';
    case MG = 'MG';
    case MS = 'MS';
    case PA = 'PA';
    case PR = 'PR';
    case RJ = 'RJ';
    case RS = 'RS';
    case SP = 'SP';

    public function label(): string
    {
        return BrazilianStateEnum::from($this->value)->label();
    }

    /**
     * Capital pré-selecionada ao escolher a UF.
     */
    public function capital(): string
    {
        return match ($this) {
            self::AM => 'Manaus',
            self::BA => 'Salvador',
            self::ES => 'Vitória',
            self::GO => 'Goiânia',
            self::MG => 'Belo Horizonte',
            self::MS => 'Campo Grande',
            self::PA => 'Belém',
            self::PR => 'Curitiba',
            self::RJ => 'Rio de Janeiro',
            self::RS => 'Porto Alegre',
            self::SP => 'São Paulo',
        };
    }

    /**
     * Averbação só no RJ; compra com alienação em todas, exceto PA.
     *
     * @return array<int, CalculationTypeEnum>
     */
    public function allowedCalculationTypes(): array
    {
        return array_values(array_filter([
            CalculationTypeEnum::GENERAL_REGISTRATION,
            $this !== self::PA ? CalculationTypeEnum::PURCHASE_WITH_FIDUCIARY_LIEN : null,
            $this === self::RJ ? CalculationTypeEnum::ECONOMIC_VALUE_ANNOTATION : null,
        ]));
    }

    /**
     * @return array<int, array{value: string, label: string, capital: string, types: array<int, int>}>
     */
    public static function options(): array
    {
        return array_map(fn (self $state) => [
            'value' => $state->value,
            'label' => $state->label(),
            'capital' => $state->capital(),
            'types' => array_map(fn (CalculationTypeEnum $type) => $type->value, $state->allowedCalculationTypes()),
        ], self::cases());
    }
}
