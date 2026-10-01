<?php

namespace App\Http\Requests;

class UpdateBankRequest extends StoreBankRequest
{
    protected function ignoreId(): ?string
    {
        return $this->route('id');
    }
}
