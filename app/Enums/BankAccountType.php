<?php

namespace App\Enums;

enum BankAccountType: int
{
    case SAVINGS = 0;
    case CHECKING = 1;

    public function label(): string
    {
        return match ($this) {
            self::SAVINGS => 'Poupança',
            self::CHECKING => 'Conta Corrente',
        };
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
