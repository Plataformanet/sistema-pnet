<?php

namespace App\Http\Requests;

class UpdateCostTypeRequest extends StoreCostTypeRequest
{
    protected function ignoreId(): ?string
    {
        return $this->route('id');
    }
}
