<?php

namespace App\Exceptions;

use App\Utils\Utils;

class InstallmentsTotalMismatchException extends FinancialEntryException
{
    public function __construct(public readonly int $installmentsTotal, public readonly int $total)
    {
        parent::__construct(sprintf(
            'A soma das parcelas (R$ %s) deve ser exatamente igual ao valor total do título (R$ %s).',
            Utils::format_coin_real($installmentsTotal / 100),
            Utils::format_coin_real($total / 100),
        ));
    }
}
