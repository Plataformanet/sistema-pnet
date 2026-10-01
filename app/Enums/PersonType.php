<?php

namespace App\Enums;

/**
 * Os valores são os mesmos de `contacts.type`, já que vendedores e proponentes
 * são papéis do contato.
 */
enum PersonType: string
{
    case INDIVIDUAL = 'PF';
    case COMPANY = 'PJ';

    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Pessoa Física',
            self::COMPANY => 'Pessoa Jurídica',
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
