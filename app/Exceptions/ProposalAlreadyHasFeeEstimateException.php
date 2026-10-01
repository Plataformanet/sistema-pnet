<?php

namespace App\Exceptions;

use Exception;

class ProposalAlreadyHasFeeEstimateException extends Exception
{
    public function __construct()
    {
        parent::__construct('Esta proposta já possui um emolumento vinculado.');
    }
}
