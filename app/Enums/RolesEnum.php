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
    case ANALYST = 'analyst';
    case CLIENT = 'client';
    case PROPERTY_SELLER = 'property_seller';

    // extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::SELLER => 'Vendedor',
            self::MANAGER => 'Gestor',
            self::FINANCIAL => 'Financeiro',
            self::PARTNER => 'Parceiro',
            self::ANALYST => 'Analista',
            self::CLIENT => 'Cliente',
            self::PROPERTY_SELLER => 'Vendedor do imóvel',
        };
    }

    /**
     * Cargos da equipe que conduz as propostas: podem ser criador e analista.
     *
     * @return array<int, string>
     */
    public static function proposalStaffLabels(): array
    {
        return [
            self::ADMIN->label(),
            self::ANALYST->label(),
        ];
    }

    /**
     * Cargos que só enxergam as propostas às quais estão vinculados
     * (parceiro, vendedor do imóvel e proponente).
     *
     * @return array<int, string>
     */
    public static function proposalRestrictedLabels(): array
    {
        return [
            self::PARTNER->label(),
            self::PROPERTY_SELLER->label(),
            self::CLIENT->label(),
        ];
    }

    public static function all()
    {
        return [
            self::ADMIN->label(),
            self::SELLER->label(),
            self::MANAGER->label(),
            self::FINANCIAL->label(),
            self::PARTNER->label(),
            self::ANALYST->label(),
            self::CLIENT->label(),
            self::PROPERTY_SELLER->label(),
        ];
    }
}
