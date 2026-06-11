<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialSubcategoryRequest extends FormRequest
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
            'financial_category_id' => 'required|integer|exists:financial_categories,id',
            'name'                  => 'required|string|max:255',
            'observations'          => 'nullable|string',
            'active'                => 'sometimes',
        ];
    }

    public function messages(): array
    {
        return [
            'financial_category_id.required' => 'A categoria é obrigatória.',
            'financial_category_id.integer'  => 'A categoria selecionada é inválida.',
            'financial_category_id.exists'   => 'A categoria selecionada não existe.',
            'name.required'                  => 'O nome é obrigatório.',
            'name.string'                    => 'O nome deve ser um texto válido.',
            'name.max'                       => 'O nome deve ter no máximo 255 caracteres.',
            'observations.string'            => 'As observações devem ser um texto válido.',
        ];
    }
}
