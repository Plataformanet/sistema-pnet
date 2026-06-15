<?php

namespace App\Http\Requests;

class UpdateAccountReceivableRequest extends StoreAccountReceivableRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
