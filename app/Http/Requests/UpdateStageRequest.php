<?php

namespace App\Http\Requests;

class UpdateStageRequest extends StoreStageRequest
{
    protected function ignoreId(): ?string
    {
        return $this->route('id');
    }
}
