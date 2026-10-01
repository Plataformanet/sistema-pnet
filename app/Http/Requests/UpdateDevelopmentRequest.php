<?php

namespace App\Http\Requests;

class UpdateDevelopmentRequest extends StoreDevelopmentRequest
{
    protected function ignoreId(): ?string
    {
        return $this->route('id');
    }
}
