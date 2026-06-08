<?php

namespace App\Enums;

enum FinancialCategoryEnum: string
{
    case EXPENSE = 'despesa';
    case INCOME = 'receita';

    public function label(): string
    {
        return match ($this) {
            self::EXPENSE => 'Despesa',
            self::INCOME => 'Receita',
        };
    }
}
