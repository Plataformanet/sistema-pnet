<?php

namespace App\Enums;

enum FinancialPaymentMethodEnum: string
{
    case CREDIT_CARD = 'credit_card';
    case TICKET = 'ticket';
    case PIX = 'pix';
    case MONEY = 'money';

    public function label(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'Cartão de Crédito',
            self::TICKET => 'Boleto',
            self::PIX => 'Pix',
            self::MONEY => 'Dinheiro',
        };
    }

}
