<?php

namespace App\Enums;

/**
 * Descontos legais repassados à API de emolumentos (o desconto é aplicado
 * pela API; o sistema só envia "{código}/{UF}").
 */
enum FeeDiscountEnum: string
{
    case FIRST_ACQUISITION_SFH = 'SFH';
    case FIRST_ACQUISITION_PUBLIC_DEED = 'EP';
    case MINHA_CASA_MINHA_VIDA = 'PCVA_MCMV';
    case FAR_FDS = 'FAR_FDS';
    case POPULAR_HOUSING = 'HAP';

    public function label(): string
    {
        return match ($this) {
            self::FIRST_ACQUISITION_SFH => '1ª Aquisição SFH',
            self::FIRST_ACQUISITION_PUBLIC_DEED => '1ª Aquisição - Escritura Pública',
            self::MINHA_CASA_MINHA_VIDA => 'Minha Casa Minha Vida',
            self::FAR_FDS => 'FAR e FDS',
            self::POPULAR_HOUSING => 'Habitação Popular',
        };
    }

    public function legalText(): string
    {
        return __('fee_calculator.discounts.'.$this->value, [], 'pt_BR');
    }

    /**
     * UFs em que o desconto pode ser escolhido (nulo = todas).
     *
     * @return array<int, SupportedStateEnum>|null
     */
    public function allowedStates(): ?array
    {
        return match ($this) {
            self::FIRST_ACQUISITION_PUBLIC_DEED => [SupportedStateEnum::RJ],
            self::POPULAR_HOUSING => [SupportedStateEnum::AM],
            default => null,
        };
    }

    public function isAllowedFor(SupportedStateEnum $state): bool
    {
        $states = $this->allowedStates();

        return $states === null || in_array($state, $states, true);
    }

    public function apiValue(SupportedStateEnum $state): string
    {
        return $this->value.'/'.$state->value;
    }

    /**
     * @return array<int, array{value: string, label: string, legal_text: string, states: array<int, string>|null}>
     */
    public static function options(): array
    {
        return array_map(fn (self $discount) => [
            'value' => $discount->value,
            'label' => $discount->label(),
            'legal_text' => $discount->legalText(),
            'states' => $discount->allowedStates() !== null
                ? array_map(fn (SupportedStateEnum $state) => $state->value, $discount->allowedStates())
                : null,
        ], self::cases());
    }
}
