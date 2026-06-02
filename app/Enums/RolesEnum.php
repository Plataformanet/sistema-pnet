<?php

namespace App\Enums;

enum RolesEnum: string
{
    // case NAMEINAPP = 'name-in-database';

    case ADMIN = 'admin';
    case SELLER = 'seller';
    case MANAGER = 'manager';
    case FINANCIAL = 'financial';
    case PARTNER = 'partner';

    // extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::SELLER => 'Vendedor',
            self::MANAGER => 'Gestor',
            self::FINANCIAL => 'Financeiro',
            self::PARTNER => 'Parceiro',
        };
    }

    public static function all()
    {
        return [
            self::ADMIN->label(),
            self::SELLER->label(),
            self::MANAGER->label(),
            self::FINANCIAL->label(),
            self::PARTNER->label(),
        ];
    }
}
