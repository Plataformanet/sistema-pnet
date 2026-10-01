<?php

namespace App\Exceptions\Itbi;

use Exception;

class ItbiNotConfiguredException extends Exception
{
    public static function municipality(): self
    {
        return new self(__('fee_calculator.messages.municipality_not_configured', [], 'pt_BR'));
    }

    public static function outOfBrackets(string $municipality): self
    {
        return new self(__('fee_calculator.messages.out_of_brackets', ['municipality' => $municipality], 'pt_BR'));
    }
}
