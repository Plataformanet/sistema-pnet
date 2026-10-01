<?php

namespace App\Http\Requests;

class UpdateContractTypeRequest extends StoreContractTypeRequest
{
    protected function ignoreId(): ?string
    {
        return $this->route('id');
    }
}
