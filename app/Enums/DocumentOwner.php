<?php

namespace App\Enums;

use App\Models\User;

enum DocumentOwner: string
{
    case BUYER = 'buyer';
    case SELLER = 'seller';
    case PROPERTY = 'property';
    case STAGE = 'stage';
    case GENERAL = 'general';

    public function label(): string
    {
        return match ($this) {
            self::BUYER => 'Comprador',
            self::SELLER => 'Vendedor',
            self::PROPERTY => 'Imóvel',
            self::STAGE => 'Etapa',
            self::GENERAL => 'Geral',
        };
    }

    /**
     * Donos de documento que o usuário pode ver numa proposta. Quem tem algum
     * cargo da equipe vê todos. Quem só tem cargos externos vê a união do que
     * cada um desses cargos libera:
     * - Parceiro: documentos das pessoas e do imóvel;
     * - Cliente: tudo, menos os do vendedor;
     * - Vendedor do imóvel: tudo, menos os do comprador.
     *
     * @return array<int, self>
     */
    public static function visibleTo(User $user): array
    {
        if (! $user->hasOnlyProposalRestrictedRoles()) {
            return self::cases();
        }

        $byRole = [
            RolesEnum::PARTNER->label() => [self::BUYER, self::SELLER, self::PROPERTY],
            RolesEnum::CLIENT->label() => [self::BUYER, self::PROPERTY, self::STAGE, self::GENERAL],
            RolesEnum::PROPERTY_SELLER->label() => [self::SELLER, self::PROPERTY, self::STAGE, self::GENERAL],
        ];

        return $user->getRoleNames()
            ->flatMap(fn (string $role) => $byRole[$role] ?? [])
            ->unique()
            ->values()
            ->all();
    }
}
