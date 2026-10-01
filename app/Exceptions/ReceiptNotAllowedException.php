<?php

namespace App\Exceptions;

use Exception;

class ReceiptNotAllowedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Esta cobrança não gera recibo.');
    }
}
