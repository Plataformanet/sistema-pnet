<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreEmployeeRequest extends StoreContactRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'rg' => 'required|string',
            'birth_date' => 'required|date',
            'position' => 'required|string',
            'salary' => 'required|numeric',
            'hire_date' => 'required|date',
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
            'rg.required' => 'O RG é obrigatório.',
            'birth_date.required' => 'A data de nascimento é obrigatória.',
            'position.required' => 'O cargo é obrigatório.',
            'salary.required' => 'O salário é obrigatório.',
            'hire_date.required' => 'A data de admissão é obrigatória.',
        ]);
    }
}
