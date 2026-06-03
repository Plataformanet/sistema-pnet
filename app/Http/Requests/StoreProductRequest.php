<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'product_category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'sku' => ['required', Rule::unique('products', 'sku')],
            'barcode' => ['required', Rule::unique('products', 'barcode')],
            'cost_value' => ['required', 'numeric', 'min:0'],
            'sell_value' => ['required', 'numeric', 'min:0'],
            'manage_stock' => ['required', 'boolean'],
            'current_stock' => ['required_if:manage_stock,true', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'unit_of_measure' => ['required', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'name.max' => 'O nome deve ter no máximo 100 caracteres.',
            'description.max' => 'A descrição deve ter no máximo 255 caracteres.',
            'product_category_id.required' => 'A categoria é obrigatória.',
            'product_category_id.exists' => 'A categoria selecionada é inválida.',
            'sku.required' => 'O SKU é obrigatório.',
            'sku.unique' => 'O SKU já está cadastrado.',
            'barcode.required' => 'O código de barras é obrigatório.',
            'barcode.unique' => 'O código de barras já está cadastrado.',
            'manage_stock.required' => 'O campo de gestão de estoque é obrigatório.',
            'current_stock.required_if' => 'O estoque atual é obrigatório quando a gestão de estoque está habilitada.',
            'current_stock.numeric' => 'O estoque atual deve ser um número.',
            'current_stock.min' => 'O estoque atual não pode ser negativo.',
            'min_stock.numeric' => 'O estoque mínimo deve ser um número.',
            'min_stock.min' => 'O estoque mínimo não pode ser negativo.',
            'unit_of_measure.required' => 'A unidade de medida é obrigatória.',
            'unit_of_measure.max' => 'A unidade de medida deve ter no máximo 10 caracteres.',
            'cost_value.required' => 'O custo é obrigatório.',
            'cost_value.numeric' => 'O custo deve ser um número.',
            'cost_value.min' => 'O custo não pode ser negativo.',
            'sell_value.required' => 'O preço de venda é obrigatório.',
            'sell_value.numeric' => 'O preço de venda deve ser um número.',
            'sell_value.min' => 'O preço de venda não pode ser negativo.',
            'status.required' => 'O status é obrigatório.',
        ];
    }
}
