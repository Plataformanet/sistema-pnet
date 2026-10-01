<?php

namespace App\Http\Requests;

use App\Rules\UniqueCatalogName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StorePropertyTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:191', new UniqueCatalogName('property_types', 'Este tipo de imóvel já está cadastrado.', $this->ignoreId())],
            'shows_number' => ['required', 'boolean'],
            'shows_complement' => ['required', 'boolean'],
            'requires_development' => ['required', 'boolean'],
            'shows_unit' => ['required', 'boolean'],
            'shows_block' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do tipo de imóvel precisa ser preenchido.',
            'name.min' => 'O nome do tipo de imóvel deve ter no mínimo 3 caracteres.',
            'name.max' => 'O nome do tipo de imóvel deve ter no máximo 191 caracteres.',
            '*.required' => 'Este campo é obrigatório.',
            '*.boolean' => 'Este campo deve ser Sim ou Não.',
        ];
    }

    protected function ignoreId(): ?string
    {
        return null;
    }
}
