<?php

namespace App\Http\Requests;

use App\Enums\PersonType;
use App\Rules\CnpjRule;
use App\Rules\CpfRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Inclusão de um vendedor na proposta já cadastrada. O documento é validado
 * como CPF ou CNPJ conforme o tipo de pessoa; vendedor já cadastrado com o
 * mesmo documento é reaproveitado com os dados que já tem.
 */
class StoreProposalSellerRequest extends UpdateProposalSellerRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge(['document' => preg_replace('/\D/', '', (string) $this->input('document'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCompany = $this->input('person_type') === PersonType::COMPANY->value;

        return [
            'person_type' => ['required', Rule::enum(PersonType::class)],
            'document' => ['required', $isCompany ? new CnpjRule : new CpfRule],
            ...parent::rules(),
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'person_type.required' => 'Selecione se o vendedor é pessoa física ou jurídica.',
            'document.required' => 'O CPF/CNPJ do vendedor é obrigatório.',
        ]);
    }
}
