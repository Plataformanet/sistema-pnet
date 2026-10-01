<?php

namespace App\Http\Requests;

use App\Models\ItbiMunicipality;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida só os campos do módulo do município. Alíquotas em percentual
 * (3 = 3%); o teto do valor financiado em centavos.
 */
class UpdateItbiRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $fields = ItbiMunicipality::find($this->route('id'))?->module?->requiredFields() ?? [];
        $required = fn (string $field) => Rule::requiredIf(in_array($field, $fields, true));

        return [
            'own_funds_rate' => ['required', 'numeric', 'between:0,100'],
            'financed_rate' => [$required('financed_rate'), 'nullable', 'numeric', 'between:0,100'],
            'financed_cap_amount' => [$required('financed_cap_amount'), 'nullable', 'integer', 'min:0'],
            'first_property_financed_rate' => [$required('first_property_financed_rate'), 'nullable', 'numeric', 'between:0,100'],
            'other_property_financed_rate' => [$required('other_property_financed_rate'), 'nullable', 'numeric', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'Este campo é obrigatório para o módulo do município.',
            '*.numeric' => 'Informe um percentual válido.',
            '*.between' => 'O percentual deve estar entre 0 e 100.',
            'financed_cap_amount.integer' => 'Informe um valor válido.',
            'financed_cap_amount.min' => 'O valor não pode ser negativo.',
        ];
    }
}
