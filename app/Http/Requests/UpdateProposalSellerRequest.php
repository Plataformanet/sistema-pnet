<?php

namespace App\Http\Requests;

use App\Enums\BankAccountType;
use App\Enums\MaritalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edição de um vendedor na tela da proposta. Documento e tipo de pessoa não
 * são editáveis; a renda chega em centavos inteiros. Conta bancária toda em
 * branco é descartada.
 */
class UpdateProposalSellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $account = $this->input('bank_account');

        if (is_array($account) && collect($account)->filter(fn ($value) => filled($value))->isEmpty()) {
            $this->merge(['bank_account' => null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:20'],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'profession' => ['nullable', 'string', 'max:191'],
            'declared_income' => ['nullable', 'integer', 'min:0'],
            'declares_income_tax' => ['boolean'],
            'income_tax_notes' => ['nullable', 'string'],
            'by_power_of_attorney' => ['boolean'],
            'bank_account' => ['nullable', 'array'],
            'bank_account.bank_name' => ['required_with:bank_account', 'string', 'max:100'],
            'bank_account.account_type' => ['required_with:bank_account', Rule::enum(BankAccountType::class)],
            'bank_account.branch' => ['required_with:bank_account', 'string', 'max:20'],
            'bank_account.number' => ['required_with:bank_account', 'string', 'max:30'],
            'bank_account.notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do vendedor é obrigatório.',
            'name.min' => 'O nome do vendedor deve ter no mínimo 3 caracteres.',
            'email.required' => 'O e-mail do vendedor é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'marital_status.enum' => 'O estado civil selecionado é inválido.',
            'declared_income.integer' => 'Valor inválido.',
            'declared_income.min' => 'O valor não pode ser negativo.',
            'bank_account.*.required_with' => 'Preencha todos os dados da conta bancária.',
            'bank_account.account_type.enum' => 'O tipo de conta selecionado é inválido.',
        ];
    }
}
