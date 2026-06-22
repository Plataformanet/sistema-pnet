<?php

namespace App\Enums;

enum ContactTypeEnum: string
{
    case CLIENT = 'cliente';
    case SUPPLIER = 'fornecedor';
    case EMPLOYEE = 'funcionário';

    public function label(): string
    {
        return match ($this) {
            self::CLIENT => 'Cliente',
            self::SUPPLIER => 'Fornecedor',
            self::EMPLOYEE => 'Funcionário',
        };
    }
}
