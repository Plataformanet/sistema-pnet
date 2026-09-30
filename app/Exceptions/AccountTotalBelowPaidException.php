<?php

namespace App\Exceptions;

use App\Utils\Utils;

class AccountTotalBelowPaidException extends FinancialEntryException
{
    public function __construct(public readonly int $paidTotal)
    {
        parent::__construct('O novo total não pode ser menor que o valor já pago (R$ '.Utils::format_coin_real($paidTotal / 100).').');
    }
}
