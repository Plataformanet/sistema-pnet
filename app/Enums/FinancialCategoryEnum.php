<?php

namespace App\Enums;

enum FinancialCategoryEnum: int
{
    case DESPESA = 1;
    case RECEITA = 2;

    public function getStatus(): string
    {
        return match ($this) {
            self::DESPESA => 'Despesa',
            self::RECEITA => 'Receita',
            default => 'Tipo não encontrado',
        };
    }

    public static function parse($status)
    {
        switch ($status) {
            case 'Despesa':
                return self::DESPESA;
            case 'Receita':
                return self::RECEITA;
            default:
                return null;
        }
    }
}
