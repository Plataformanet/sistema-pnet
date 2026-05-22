<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
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
            'name'          => 'required|string|max:255|unique:roles,name',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'O nome do cargo é obrigatório.',
            'name.max'              => 'O nome do cargo deve ter no máximo 255 caracteres.',
            'name.unique'           => 'Já existe um cargo com este nome.',
            'permissions.array'     => 'As permissões devem ser uma lista válida.',
            'permissions.*.exists'  => 'Uma das permissões selecionadas é inválida.',
        ];
    }
}
