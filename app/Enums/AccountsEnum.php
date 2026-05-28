<?php

namespace App\Enums;

enum AccountsEnum: int
{
    case OPEN = 1;
    case OVERDUE = 2;
    case PAID = 3;
    case RECEIVED = 4;

    public function getStatus(): string
    {
        return match ($this) {
            self::OPEN => 'Em Aberto',
            self::OVERDUE => 'Vencido',
            self::PAID => 'Pago',
            self::RECEIVED => 'Recebido',
            default => 'Status não encontrado',
        };
    }

    public function getStyles(): string
    {
        return match ($this) {
            self::OPEN => 'background: #fff3cd;color: #664d03; font-weight: bold; text-align: center;display:flex;height: 2rem;border-radius: 0.3rem;align-items: center;justify-content: center;',
            self::OVERDUE => 'Vencido',
            self::PAID => 'background: #d1e7dd;color: #0a3622; font-weight: bold; text-align: center;display:flex;height: 2rem;border-radius: 0.3rem;align-items: center;justify-content: center;',
            self::RECEIVED => 'background: #d1e7dd;color: #0a3622; font-weight: bold; text-align: center;display:flex;height: 2rem;border-radius: 0.3rem;align-items: center;justify-content: center;',
            default => 'Status não encontrado',
        };
    }

    public static function parse($status)
    {
        return match ($status) {
            'Em Aberto' => self::OPEN,
            'Vencido' => self::OVERDUE,
            'Pago' => self::PAID,
            'Recebido' => self::RECEIVED,
            default => null,
        };
    }
}
