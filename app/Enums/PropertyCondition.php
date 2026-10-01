<?php

namespace App\Enums;

enum PropertyCondition: string
{
    case NEW = 'new';
    case USED = 'used';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Novo',
            self::USED => 'Usado',
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
