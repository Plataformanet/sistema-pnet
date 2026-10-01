<?php

namespace App\Enums;

/**
 * Tipo da linha de custo da proposta. Emolumento, taxa extra, serviço e ITBI
 * são gerados pela calculadora; "Manual" é o lançamento feito na proposta.
 */
enum CostItemType: string
{
    case EMOLUMENT = 'emolument';
    case EXTRA_FEE = 'extra_fee';
    case SERVICE = 'service';
    case ITBI = 'itbi';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::EMOLUMENT => 'Emolumento',
            self::EXTRA_FEE => 'Taxa extra',
            self::SERVICE => 'Serviço',
            self::ITBI => 'ITBI',
            self::MANUAL => 'Lançamento manual',
        };
    }

    /**
     * Tipos somados no "total de emolumentos" da proposta.
     *
     * @return array<int, self>
     */
    public static function fees(): array
    {
        return [self::EMOLUMENT, self::EXTRA_FEE];
    }
}
