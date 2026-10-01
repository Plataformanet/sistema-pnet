<?php

namespace App\Policies;

use App\Enums\RolesEnum;
use App\Models\FeeCalculation;
use App\Models\User;

/**
 * O cálculo é do usuário que o fez; o administrador vê qualquer cálculo.
 */
class FeeCalculationPolicy
{
    public function view(User $user, FeeCalculation $calculation): bool
    {
        return $calculation->user_id === $user->id || $user->hasRole(RolesEnum::ADMIN->label());
    }
}
