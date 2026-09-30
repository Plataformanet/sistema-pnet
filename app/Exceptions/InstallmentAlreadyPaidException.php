<?php

namespace App\Exceptions;

class InstallmentAlreadyPaidException extends FinancialEntryException
{
    public function __construct()
    {
        parent::__construct('Esta parcela já foi paga.');
    }
}
