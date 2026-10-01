<?php

namespace App\Http\Requests;

use App\Enums\BrazilianState;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreNotaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * O CEP chega com máscara (00000-000) e é gravado só com dígitos.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'zip_code' => preg_replace('/\D/', '', (string) $this->input('zip_code')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:191'],
            'zip_code' => ['required', 'digits:8'],
            'street' => ['required', 'string', 'max:191'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:45'],
            'neighborhood' => ['required', 'string', 'max:45'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', Rule::enum(BrazilianState::class)],
            'reference_point' => ['nullable', 'string', 'max:100'],
            'business_hours' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do cartório precisa ser preenchido.',
            'name.min' => 'O nome do cartório deve ter no mínimo 3 caracteres.',
            'name.max' => 'O nome do cartório deve ter no máximo 191 caracteres.',
            'zip_code.required' => 'O CEP precisa ser preenchido.',
            'zip_code.digits' => 'O CEP deve ter 8 dígitos.',
            'street.required' => 'O endereço precisa ser preenchido.',
            'street.max' => 'O endereço deve ter no máximo 191 caracteres.',
            'number.required' => 'O número precisa ser preenchido.',
            'number.max' => 'O número deve ter no máximo 20 caracteres.',
            'complement.max' => 'O complemento deve ter no máximo 45 caracteres.',
            'neighborhood.required' => 'O bairro precisa ser preenchido.',
            'neighborhood.max' => 'O bairro deve ter no máximo 45 caracteres.',
            'city.required' => 'A cidade precisa ser preenchida.',
            'city.max' => 'A cidade deve ter no máximo 80 caracteres.',
            'state.required' => 'O estado precisa ser selecionado.',
            'state.enum' => 'O estado selecionado é inválido.',
            'reference_point.max' => 'O ponto de referência deve ter no máximo 100 caracteres.',
        ];
    }
}
