<?php

namespace App\Http\Requests;

use App\Rules\UniqueCatalogName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreBankRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100', new UniqueCatalogName('banks', 'Este banco já está cadastrado.', $this->ignoreId())],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do banco precisa ser preenchido.',
            'name.max' => 'O nome do banco deve ter no máximo 100 caracteres.',
        ];
    }

    protected function ignoreId(): ?string
    {
        return null;
    }
}
