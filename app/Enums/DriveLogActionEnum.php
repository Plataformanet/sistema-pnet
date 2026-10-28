<?php

namespace App\Enums;

/**
 * Ações do Drive que geram log (RN-DRV-05).
 */
enum DriveLogActionEnum: string
{
    case UPLOAD = 'upload';
    case DOWNLOAD = 'download';
    case MOVE = 'move';
    case DELETE = 'delete';
    case RESTORE = 'restore';
    case PERMANENT_DELETE = 'permanent_delete';

    public function label(): string
    {
        return match ($this) {
            self::UPLOAD => 'Upload',
            self::DOWNLOAD => 'Download',
            self::MOVE => 'Movimentação',
            self::DELETE => 'Exclusão',
            self::RESTORE => 'Restauração',
            self::PERMANENT_DELETE => 'Exclusão definitiva',
        };
    }
}
