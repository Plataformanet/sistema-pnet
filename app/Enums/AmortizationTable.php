<?php

namespace App\Enums;

enum AmortizationTable: string
{
    case PRICE = 'price';
    case SAC = 'sac';

    public function label(): string
    {
        return match ($this) {
            self::PRICE => 'PRICE',
            self::SAC => 'SAC',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
