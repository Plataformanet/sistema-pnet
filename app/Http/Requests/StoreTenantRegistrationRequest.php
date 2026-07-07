<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreTenantRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza o domínio antes de validar: sem espaços e em minúsculas, para
     * evitar hosts inválidos (ex.: com `@`, maiúsculas ou espaços) que quebram a
     * identificação do tenant e o redirecionamento para o login.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('domain')) {
            $this->merge([
                'domain' => Str::lower(trim((string) $this->input('domain'))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'domain' => [
                'required',
                'string',
                'max:255',
                // Host válido: rótulos alfanuméricos/hífen separados por ponto,
                // exigindo ao menos um ponto (ex.: "empresa.localhost").
                'regex:/^(?!-)[a-z0-9-]+(?:\.[a-z0-9-]+)+$/',
                Rule::notIn(config('tenancy.central_domains')),
                Rule::unique('domains', 'domain'),
            ],
            'plan_id' => 'required|exists:plans,id',
            'userName' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string',
            // 'password' => 'required|string|confirmed|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do tenant é obrigatório.',
            'domain.required' => 'O domínio é obrigatório.',
            'domain.regex' => 'O domínio deve ser um host válido, como "empresa.localhost" (sem espaços, "@" ou caracteres especiais).',
            'domain.not_in' => 'Este domínio é reservado pelo sistema.',
            'domain.unique' => 'Este domínio já está em uso.',
            'plan_id.required' => 'O plano é obrigatório.',
            'plan_id.exists' => 'O plano selecionado não é válido.',
            'userName.required' => 'O nome do usuário é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ser um endereço de email válido.',
            'email.unique' => 'Este email já está em uso.',
            'password.required' => 'A senha é obrigatória.',
            'password.confirmed' => 'A confirmação da senha não corresponde.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
        ];
    }
}
