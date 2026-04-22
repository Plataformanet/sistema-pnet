<?php

namespace App\Enums;

enum SubscriptionStatusEnum: string
{
    case ACTIVE = 'active';           // Ativa e em dia
    case TRIALING = 'trialing';       // Em período de teste
    case PAST_DUE = 'past_due';       // Pagamento atrasado
    case PAUSED = 'paused';           // Pausada pelo usuário
    case CANCELLED = 'cancelled';     // Cancelada (ainda ativa até ends_at)
    case EXPIRED = 'expired';         // Expirada definitivamente

    public function isActive(): bool
    {
        return in_array($this, [
            self::ACTIVE,
            self::TRIALING,
            self::CANCELLED, // ainda válida até ends_at
        ]);
    }
}
