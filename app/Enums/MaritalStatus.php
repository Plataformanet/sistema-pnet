<?php

namespace App\Enums;

enum MaritalStatus: int
{
    case SINGLE = 1;
    case MARRIED = 2;
    case WIDOWED = 3;
    case JUDICIALLY_SEPARATED = 4;
    case DIVORCED = 5;

    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Solteiro(a)',
            self::MARRIED => 'Casado(a)',
            self::WIDOWED => 'Viúvo(a)',
            self::JUDICIALLY_SEPARATED => 'Separado(a) judicialmente',
            self::DIVORCED => 'Divorciado(a)',
        };
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }
}
