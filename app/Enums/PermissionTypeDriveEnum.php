<?php

namespace App\Enums;

enum PermissionTypeDriveEnum: string
{
    case SOMENTE_PROPRIETARIO = 'somente_proprietario';
    case SOMENTE_LEITURA = 'somente_leitura';
    case ACESSO_TOTAL = 'acesso_total';

    public function getType(): string
    {
        return match ($this) {
            self::SOMENTE_PROPRIETARIO => 'somente_proprietario',
            self::SOMENTE_LEITURA => 'somente_leitura',
            self::ACESSO_TOTAL => 'acesso_total',
        };
    }
}
