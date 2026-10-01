<?php

namespace App\Http\Requests;

use App\Rules\NonOverlappingBrackets;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Faixas do módulo 02: valores em centavos, limites inclusivos e sem sobreposição.
 */
class UpdateItbiBracketsRequest extends FormRequest
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
        return [
            'full_rate' => ['required', 'numeric', 'between:0,100'],
            'brackets' => ['required', 'array', 'min:1'],
            'brackets.*.min_value' => ['required', 'integer', 'min:0'],
            'brackets.*.max_value' => ['required', 'integer', 'min:0'],
            'brackets.*.rate' => ['required', 'numeric', 'between:0,100'],
            'brackets.*.discount_amount' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [new NonOverlappingBrackets];
    }

    public function messages(): array
    {
        return [
            'full_rate.required' => 'A alíquota cheia (SFI / registro em geral) é obrigatória.',
            'full_rate.numeric' => 'Informe um percentual válido.',
            'brackets.required' => 'Cadastre ao menos uma faixa.',
            'brackets.min' => 'Cadastre ao menos uma faixa.',
            'brackets.*.min_value.required' => 'O valor mínimo é obrigatório.',
            'brackets.*.max_value.required' => 'O valor máximo é obrigatório.',
            'brackets.*.rate.required' => 'A alíquota é obrigatória.',
            'brackets.*.rate.between' => 'A alíquota deve estar entre 0 e 100.',
            '*.integer' => 'Informe um valor válido.',
            '*.min' => 'O valor não pode ser negativo.',
        ];
    }
}
