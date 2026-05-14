<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends StoreServiceRequest
{

    public function rules(): array
    {
        $id = $this->route('id');

        return array_merge(parent::rules(), [
            'sku' => ['required', 'string', Rule::unique('products', 'sku')->ignore($id)],
        ]);
    }

}
