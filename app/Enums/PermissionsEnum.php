<?php

namespace App\Enums;

enum PermissionsEnum: string
{
    // case NAMEINAPP = 'name-in-database';

    case ADMIN = 'admin';
    case SELLER = 'seller';
    case MANAGER = 'manager';
    case FINANCIAL = 'financial';

    // extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
    public function label(): string
    {
        return match ($this) {
            static::ADMIN => 'Admin',
            static::SELLER => 'Vendedor',
            static::MANAGER => 'Gestor',
            static::FINANCIAL => 'Financeiro',
        };
    }
}
