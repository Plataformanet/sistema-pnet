<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachFeeEstimateRequest extends FormRequest
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
            'proposal_id' => ['required', 'integer', Rule::exists('proposals', 'id')->withoutTrashed()],
            'services' => ['nullable', 'array'],
            'services.*.id' => ['required', 'integer', Rule::exists('billable_services', 'id')->withoutTrashed()],
            'services.*.amount' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'proposal_id.required' => 'Selecione a proposta.',
            'proposal_id.exists' => 'A proposta selecionada não existe.',
            'services.*.id.exists' => 'Serviço inválido.',
            'services.*.amount.integer' => 'O valor do serviço é inválido.',
            'services.*.amount.min' => 'O valor do serviço não pode ser negativo.',
        ];
    }
}
