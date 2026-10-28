<?php

namespace App\Exceptions;

use Exception;

class BankAccountHasFinancialEntriesException extends Exception
{
    public function __construct()
    {
        parent::__construct('Não é possível excluir esta conta bancária porque existem lançamentos financeiros vinculados a ela.');
    }
}
