<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreSupplierRequest extends StoreContactRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'responsible_person' => 'nullable|string',
            'description' => 'required|string',
            'supply_category' => 'required|string',
        ]);
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'description.required' => 'A descrição é obrigatória.',
            'supply_category.required' => 'A categoria de fornecimento é obrigatória.',
        ]);
    }
}
