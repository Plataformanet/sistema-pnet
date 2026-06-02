<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $id = $this->route('id');

        return array_merge(parent::rules(), [
            'sku' => ['required', 'string', Rule::unique('products', 'sku')->ignore($id)],
            'barcode' => ['required', 'string', Rule::unique('products', 'barcode')->ignore($id)],
        ]);
    }
}
