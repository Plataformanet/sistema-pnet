<?php

namespace App\Exceptions;

use Exception;

class PersonAlreadyInProposalException extends Exception
{
    public function __construct(string $role)
    {
        parent::__construct("Este {$role} já está vinculado à proposta.");
    }
}
