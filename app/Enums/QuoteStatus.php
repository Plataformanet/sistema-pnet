<?php

namespace App\Enums;

/**
 * Status do orçamento, derivado (não persistido).
 */
enum QuoteStatus: string
{
    case OPEN = 'open';
    case EXPIRED = 'expired';
    case CONVERTED = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Em aberto',
            self::EXPIRED => 'Expirado',
            self::CONVERTED => 'Convertido em proposta',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::OPEN => 'default',
            self::EXPIRED => 'destructive',
            self::CONVERTED => 'success',
        };
    }
}
