<?php

namespace App\Http\Requests;

use App\Rules\CnpjRule;
use App\Rules\CpfRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

/**
 * Recibo geral (sem cobrança, com totais) ou de assessoria/motoboy vinculado
 * a uma cobrança. Valores em centavos.
 */
class StoreReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['document' => preg_replace('/\D/', '', (string) $this->input('document'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proposal_cost_item_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:191'],
            'document' => ['required', 'string'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'total_spent' => ['nullable', 'required_without:proposal_cost_item_id', 'integer', 'min:0'],
            'amount_deposited' => ['nullable', 'required_without:proposal_cost_item_id', 'integer', 'min:0'],
            'date' => ['required', 'date'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $document = (string) $this->input('document');
                $rule = strlen($document) > 11 ? new CnpjRule : new CpfRule;
                $check = ValidatorFacade::make(['document' => $document], ['document' => [$rule]]);

                if ($document !== '' && $check->fails()) {
                    $validator->errors()->add('document', 'Informe um CPF ou CNPJ válido.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'document.required' => 'O CPF/CNPJ é obrigatório.',
            'total_spent.required_without' => 'O total gasto é obrigatório no recibo geral.',
            'amount_deposited.required_without' => 'O valor depositado é obrigatório no recibo geral.',
            'total_spent.integer' => 'O total gasto é inválido.',
            'amount_deposited.integer' => 'O valor depositado é inválido.',
            'date.required' => 'A data é obrigatória.',
            'date.date' => 'Informe uma data válida.',
        ];
    }
}
