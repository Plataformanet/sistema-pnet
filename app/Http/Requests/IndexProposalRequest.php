<?php

namespace App\Http\Requests;

use App\Enums\ProposalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProposalRequest extends FormRequest
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
            'status' => ['nullable', Rule::enum(ProposalStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'ids' => ['nullable', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'O status selecionado é inválido.',
            'search.max' => 'A busca deve ter no máximo 100 caracteres.',
            'ids.max' => 'Selecione no máximo 500 propostas para imprimir.',
            'ids.*.integer' => 'Proposta selecionada inválida.',
        ];
    }
}
