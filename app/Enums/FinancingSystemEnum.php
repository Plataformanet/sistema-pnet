<?php

namespace App\Enums;

enum FinancingSystemEnum: string
{
    case SFH = 'sfh';
    case SFI = 'sfi';

    public function label(): string
    {
        return match ($this) {
            self::SFH => 'SFH',
            self::SFI => 'SFI',
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
