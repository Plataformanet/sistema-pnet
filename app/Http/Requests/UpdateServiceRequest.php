<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateServiceRequest extends StoreServiceRequest
{
    public function rules(): array
    {
        $id = $this->route('id');

        return array_merge(parent::rules(), [
            'sku' => ['required', Rule::unique('services', 'sku')->ignore($id)],
        ]);
    }
}
