<?php

namespace App\Http\Requests;

class UpdateBankAccountRequest extends StoreBankAccountRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
