<?php

namespace App\Enums;

enum AccountsEnum: string
{
    case OPEN = 'open';
    case OVERDUE = 'overdue';
    case PAID = 'paid';
    case RECEIVED = 'received';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Em Aberto',
            self::OVERDUE => 'Vencido',
            self::PAID => 'Pago',
            self::RECEIVED => 'Recebido',
        };
    }

    public function getStyles(): string
    {
        $base = 'font-weight: bold; text-align: center; display: flex; height: 2rem; border-radius: 0.3rem; align-items: center; justify-content: center;';

        return match ($this) {
            self::OPEN => "background: #fff3cd; color: #664d03; {$base}",
            self::OVERDUE => "background: #f8d7da; color: #58151c; {$base}",
            self::PAID, self::RECEIVED => "background: #d1e7dd; color: #0a3622; {$base}",
        };
    }
}
