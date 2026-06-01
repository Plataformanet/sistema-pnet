<?php

namespace App\Http\Requests;

class UpdateFinancialCategoryRequest extends StoreFinancialCategoryRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
