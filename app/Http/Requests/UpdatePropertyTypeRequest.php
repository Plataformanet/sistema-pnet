<?php

namespace App\Http\Requests;

class UpdatePropertyTypeRequest extends StorePropertyTypeRequest
{
    protected function ignoreId(): ?string
    {
        return $this->route('id');
    }
}
