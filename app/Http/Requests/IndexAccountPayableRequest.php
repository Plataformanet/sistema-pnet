<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAccountPayableRequest extends FormRequest
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
            'periodo' => 'nullable|string|date_format:Y-m',
            'quantidade' => 'nullable|integer|min:1',
            'inicio' => 'nullable|date',
            'fim' => 'nullable|date|after_or_equal:inicio',
            'status' => ['nullable', Rule::in(['pago', 'a-vencer', 'vencem-hoje', 'vencidos'])],
            'dias' => 'nullable|integer|min:0',
            'conta_id' => 'nullable|integer|exists:bank_accounts,id',
            'categoria_id' => 'nullable|integer|exists:financial_categories,id',
            'search' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'periodo.string' => 'O período deve ser um texto válido.',
            'periodo.date_format' => 'O período deve estar no formato AAAA-MM.',
            'quantidade.integer' => 'A quantidade deve ser um valor inteiro.',
            'quantidade.min' => 'A quantidade deve ser no mínimo 1.',
            'inicio.date' => 'A data inicial é inválida.',
            'fim.date' => 'A data final é inválida.',
            'fim.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'status.in' => 'O status selecionado é inválido.',
            'dias.integer' => 'O número de dias deve ser um valor inteiro.',
            'dias.min' => 'O número de dias não pode ser negativo.',
            'conta_id.integer' => 'A conta bancária é inválida.',
            'conta_id.exists' => 'A conta bancária selecionada não existe.',
            'categoria_id.integer' => 'A categoria financeira é inválida.',
            'categoria_id.exists' => 'A categoria financeira selecionada não existe.',
            'search.string' => 'O termo de busca deve ser um texto válido.',
            'search.max' => 'O termo de busca deve ter no máximo 255 caracteres.',
        ];
    }
}
