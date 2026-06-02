<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:191',
            'type'         => 'required|integer|in:1,2',
            'observations' => 'nullable|string',
            'active'       => 'sometimes',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'O nome é obrigatório.',
            'name.string'         => 'O nome deve ser um texto válido.',
            'name.max'            => 'O nome deve ter no máximo 191 caracteres.',
            'type.required'       => 'O tipo é obrigatório.',
            'type.integer'        => 'O tipo deve ser um número inteiro.',
            'type.in'             => 'O tipo selecionado é inválido.',
            'observations.string' => 'As observações devem ser um texto válido.',
        ];
    }
}
