<?php

namespace App\Enums;

enum ReceiptType: string
{
    case GENERAL = 'general';
    case ADVISORY = 'advisory';
    case COURIER = 'courier';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'Geral',
            self::ADVISORY => 'Assessoria',
            self::COURIER => 'Motoboy',
        };
    }

    /**
     * Tipos que um tipo de custo pode gerar. O recibo geral não é vinculado a
     * uma cobrança, por isso não faz sentido no cadastro de tipos de custo.
     *
     * @return array<int, self>
     */
    public static function forCostTypes(): array
    {
        return [self::ADVISORY, self::COURIER];
    }

    /**
     * @param  array<int, self>|null  $cases
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(?array $cases = null): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            $cases ?? self::cases(),
        );
    }
}
