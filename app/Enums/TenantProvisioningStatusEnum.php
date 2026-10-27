<?php

namespace App\Enums;

enum TenantProvisioningStatusEnum: string
{
    case PENDING = 'pending';
    case READY = 'ready';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Provisionando',
            self::READY => 'Pronto',
            self::FAILED => 'Falhou',
        };
    }
}
