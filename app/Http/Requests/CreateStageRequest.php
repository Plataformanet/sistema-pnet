<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Timeline do formulário de nova etapa: só banco ativo recebe etapa nova.
 */
class CreateStageRequest extends FormRequest
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
            'bank_id' => ['nullable', 'integer', Rule::exists('banks', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_id.integer' => 'O banco da timeline é inválido.',
            'bank_id.exists' => 'O banco da timeline não existe ou foi excluído.',
        ];
    }
}
