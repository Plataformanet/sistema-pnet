<?php

namespace App\Http\Requests;

use App\Rules\UniqueCatalogName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreContractTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => Str::squish((string) $this->input('name'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191', new UniqueCatalogName('contract_types', 'Este tipo de contrato já está cadastrado.', $this->ignoreId())],
            'requires_financing' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do tipo de contrato precisa ser preenchido.',
            'name.max' => 'O nome do tipo de contrato deve ter no máximo 191 caracteres.',
            'requires_financing.required' => 'Informe se o contrato exige dados de financiamento.',
            'requires_financing.boolean' => 'O campo "Exige dados de financiamento" é inválido.',
        ];
    }

    protected function ignoreId(): ?string
    {
        return null;
    }
}
