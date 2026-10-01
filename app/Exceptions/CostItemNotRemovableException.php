<?php

namespace App\Exceptions;

use Exception;

class CostItemNotRemovableException extends Exception
{
    public function __construct()
    {
        parent::__construct('Somente lançamentos manuais podem ser excluídos. Nas linhas geradas pela calculadora apenas o valor pode ser editado.');
    }
}
