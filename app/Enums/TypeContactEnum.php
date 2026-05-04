<?php

namespace App\Enums;

enum TypeContactEnum: string
{
    case CLIENT = 'cliente';
    case SUPPLIER = 'fornecedor';
    case EMPLOYEE = 'funcionário';

    public function type(): string
    {
        return match ($this) {
            self::CLIENT => 'Cliente',
            self::SUPPLIER => 'Fornecedor',
            self::EMPLOYEE => 'Funcionário',
        };
    }
}
