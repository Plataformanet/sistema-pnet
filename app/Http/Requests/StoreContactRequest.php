<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
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
            'type'                 => 'required|in:PF,PJ',
            'name_corporatereason' => 'required_if:type,PJ|string|max:255',
            'fantasy_name'         => 'nullable|string|max:255',
            'cpf_cnpj'             => 'required|string',
            'email'                => 'nullable|email',
            'phone'                => 'nullable|string',
            'cell_phone'           => 'nullable|string',
            'rg'                   => 'sometimes',
            'birth_date'           => 'sometimes|date',
            'position'             => 'sometimes',
            'salary'               => 'sometimes|numeric',
            'hire_date'            => 'sometimes|date',
            'responsible_person'   => 'sometimes',
            'description'          => 'sometimes',
            'supply_category'      => 'sometimes',
            'zip_code'             => 'required',
            'street'               => 'required',
            'number'               => 'required',
            'complement'           => 'nullable',
            'neighborhood'         => 'required',
            'city'                 => 'required',
            'state'                => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'                    => 'O tipo é obrigatório.',
            'type.in'                          => 'O tipo deve ser Pessoa Física ou Pessoa Jurídica.',
            'name_corporatereason.required_if' => 'O nome ou razão social é obrigatório quando o tipo é Pessoa Jurídica.',
            'name_corporatereason.max'         => 'O nome ou razão social deve ter no máximo 255 caracteres.',
            'fantasy_name.max'                 => 'O nome fantasia deve ter no máximo 255 caracteres.',
            'cpf_cnpj.required'                => 'O CPF/CNPJ é obrigatório.',
            'email.email'                      => 'O e-mail deve ter no máximo 255 caracteres.',
            'zip_code.required'                => 'O CEP é obrigatório.',
            'street.required'                  => 'A rua é obrigatória.',
            'number.required'                  => 'O número é obrigatório.',
            'neighborhood.required'            => 'O bairro é obrigatório.',
            'city.required'                    => 'A cidade é obrigatória.',
            'state.required'                   => 'O estado é obrigatório.',
        ];
    }
}
