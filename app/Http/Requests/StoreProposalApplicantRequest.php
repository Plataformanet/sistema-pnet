<?php

namespace App\Http\Requests;

use App\Rules\CpfRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Inclusão de um proponente na proposta já cadastrada. Como na criação da
 * proposta, o proponente já existente (reaproveitado pelo CPF) só precisa do
 * CPF; o novo exige os mesmos dados da edição.
 */
class StoreProposalApplicantRequest extends UpdateProposalApplicantRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge(['cpf' => preg_replace('/\D/', '', (string) $this->input('cpf'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'existing' => ['boolean'],
            'cpf' => ['required', new CpfRule],
            ...array_map(fn (array $rules) => ['exclude_if:existing,true', ...$rules], parent::rules()),
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'cpf.required' => 'O CPF do proponente é obrigatório.',
        ]);
    }
}
