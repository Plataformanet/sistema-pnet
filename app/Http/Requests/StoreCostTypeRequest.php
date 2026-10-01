<?php

namespace App\Http\Requests;

use App\Enums\ReceiptType;
use App\Rules\UniqueCatalogName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCostTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'receipt_type' => $this->input('receipt_type') ?: null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', new UniqueCatalogName('cost_types', 'Este tipo de custo já está cadastrado.', $this->ignoreId())],
            'requires_notary' => ['required', 'boolean'],
            'receipt_type' => ['nullable', Rule::enum(ReceiptType::class)->only(ReceiptType::forCostTypes())],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do tipo de custo precisa ser preenchido.',
            'name.max' => 'O nome do tipo de custo deve ter no máximo 100 caracteres.',
            'requires_notary.required' => 'Informe se o custo deve ser vinculado a um cartório.',
            'requires_notary.boolean' => 'O campo "Vincular este custo a cartório?" é inválido.',
            'receipt_type.enum' => 'O tipo de recibo selecionado é inválido.',
        ];
    }

    protected function ignoreId(): ?string
    {
        return null;
    }
}
