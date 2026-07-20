<?php

namespace App\Exceptions;

use App\Enums\ContactTypeEnum;
use Exception;

class ContactHasFinancialEntriesException extends Exception
{
    public function __construct(public readonly ContactTypeEnum $type)
    {
        parent::__construct("Não é possível excluir este {$type->value} porque existem lançamentos financeiros vinculados a ele.");
    }
}
