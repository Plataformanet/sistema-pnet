<?php

namespace App\Exceptions;

use Exception;

class LastApplicantException extends Exception
{
    public function __construct()
    {
        parent::__construct('A proposta precisa de ao menos um proponente. Adicione outro antes de remover este.');
    }
}
