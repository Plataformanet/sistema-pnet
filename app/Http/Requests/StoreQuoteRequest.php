<?php

namespace App\Http\Requests;

use App\Enums\MaritalStatus;
use App\Rules\CpfRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * O cálculo vem pela rota (UUID), nunca do formulário. Valores de serviço em centavos.
 */
class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['cpf' => preg_replace('/\D/', '', (string) $this->input('cpf'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'cpf' => ['required', new CpfRule],
            'email' => ['required', 'email', 'max:191'],
            'phone' => ['required', 'string', 'max:20'],
            'profession' => ['required', 'string', 'max:191'],
            'marital_status' => ['required', Rule::enum(MaritalStatus::class)],
            'bank_id' => ['required', 'integer', $this->bankRule()],
            'valid_until' => ['required', 'date', 'after_or_equal:today'],
            'services' => ['nullable', 'array'],
            'services.*.id' => ['required', 'integer', Rule::exists('billable_services', 'id')->withoutTrashed()],
            'services.*.amount' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'cpf.required' => 'O CPF é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'phone.required' => 'O telefone é obrigatório.',
            'profession.required' => 'A profissão é obrigatória.',
            'marital_status.required' => 'Selecione o estado civil.',
            'marital_status.enum' => 'Estado civil inválido.',
            'bank_id.required' => 'Selecione o banco.',
            'bank_id.exists' => 'O banco selecionado não está disponível.',
            'valid_until.required' => 'A validade é obrigatória.',
            'valid_until.date' => 'Informe uma data de validade válida.',
            'valid_until.after_or_equal' => 'A validade não pode ser anterior a hoje.',
            'services.*.id.exists' => 'Serviço inválido.',
            'services.*.amount.integer' => 'O valor do serviço é inválido.',
            'services.*.amount.min' => 'O valor do serviço não pode ser negativo.',
        ];
    }

    protected function bankRule(): Exists
    {
        return Rule::exists('banks', 'id')->withoutTrashed();
    }
}
