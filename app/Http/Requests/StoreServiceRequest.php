<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_service_id' => ['required', 'integer', 'exists:category_services,id'],
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'sku' => ['required', Rule::unique('services', 'sku')],
            'cost_value' => ['required', 'numeric', 'min:0'],
            'sell_value' => ['required', 'numeric', 'min:0'],
            'fees' => ['required', 'numeric', 'min:0'],
            'duration' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_service_id.required' => 'A categoria é obrigatória.',
            'category_service_id.exists' => 'A categoria selecionada é inválida.',
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'name.max' => 'O nome deve ter no máximo 100 caracteres.',
            'sku.required' => 'O SKU é obrigatório.',
            'sku.unique' => 'O SKU já está cadastrado.',
            'cost_value.required' => 'O custo é obrigatório.',
            'cost_value.numeric' => 'O custo deve ser um número.',
            'cost_value.min' => 'O custo não pode ser negativo.',
            'sell_value.required' => 'O preço de venda é obrigatório.',
            'sell_value.numeric' => 'O preço de venda deve ser um número.',
            'sell_value.min' => 'O preço de venda não pode ser negativo.',
            'fees.required' => 'A taxa/comissão é obrigatória.',
            'fees.numeric' => 'A taxa/comissão deve ser um número.',
            'fees.min' => 'A taxa/comissão não pode ser negativa.',
            'duration.numeric' => 'A duração deve ser um número.',
            'duration.min' => 'A duração não pode ser negativa.',
            'description.max' => 'A descrição deve ter no máximo 255 caracteres.',
            'status.required' => 'O status é obrigatório.',
        ];
    }
}
