<?php

namespace App\Exceptions;

class PaidInstallmentLockedException extends FinancialEntryException
{
    public function __construct()
    {
        parent::__construct('O valor de uma parcela paga não pode ser alterado.');
    }
}
