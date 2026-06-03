<?php

namespace App\Http\Requests;

class UpdateFinancialSubcategoryRequest extends StoreFinancialSubcategoryRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
