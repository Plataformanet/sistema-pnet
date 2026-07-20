<?php

namespace App\Exceptions;

use App\Enums\ContactTypeEnum;
use Exception;

class InactiveContactException extends Exception
{
    public function __construct(public readonly ContactTypeEnum $type)
    {
        parent::__construct("Este {$type->value} está inativo e não pode receber novos lançamentos financeiros.");
    }
}
