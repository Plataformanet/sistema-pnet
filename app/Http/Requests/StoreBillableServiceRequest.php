<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreBillableServiceRequest extends FormRequest
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
     * O preço chega em centavos inteiros do MoneyInput; string formatada
     * ("1.500,00") é rejeitada em vez de convertida.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'generates_receipt' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do serviço precisa ser preenchido.',
            'name.max' => 'O nome do serviço deve ter no máximo 191 caracteres.',
            'description.required' => 'A descrição do serviço precisa ser preenchida.',
            'price.required' => 'O valor do serviço precisa ser preenchido.',
            'price.integer' => 'O valor do serviço é inválido.',
            'price.min' => 'O valor do serviço não pode ser negativo.',
            'generates_receipt.required' => 'Informe se o serviço gera recibo.',
            'generates_receipt.boolean' => 'O campo "Gerar recibo" é inválido.',
        ];
    }
}
