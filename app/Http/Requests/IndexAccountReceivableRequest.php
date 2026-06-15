<?php

namespace App\Http\Requests;


class IndexAccountReceivableRequest extends IndexAccountPayableRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
