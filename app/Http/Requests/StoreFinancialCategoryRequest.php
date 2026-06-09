<?php

namespace App\Http\Requests;

use App\Enums\FinancialCategoryEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'name'         => 'required|string|max:255',
            'type'         => ['required', Rule::enum(FinancialCategoryEnum::class)],
            'observations' => 'nullable|string',
            'active'       => 'sometimes',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'O nome é obrigatório.',
            'name.string'         => 'O nome deve ser um texto válido.',
            'name.max'            => 'O nome deve ter no máximo 255 caracteres.',
            'type.required'       => 'O tipo é obrigatório.',
            'type.enum'           => 'O tipo selecionado é inválido.',
            'observations.string' => 'As observações devem ser um texto válido.',
        ];
    }
}
