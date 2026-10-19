<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexStageRequest extends IndexCatalogRequest
{
    /**
     * O banco pode estar excluído, para ainda ser possível ver a timeline dele.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'bank_id' => ['nullable', 'integer', Rule::exists('banks', 'id')],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'bank_id.integer' => 'O banco selecionado é inválido.',
            'bank_id.exists' => 'O banco selecionado não existe.',
        ]);
    }
}
