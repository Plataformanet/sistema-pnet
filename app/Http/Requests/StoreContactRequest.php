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
            'type' => 'sometimes|in:PF,PJ',
            'name_corporatereason' => 'required|string|max:255',
            'fantasy_name' => 'nullable|string|max:255',
            'cpf_cnpj' => 'required|string',
            'email' => 'required|email|max:255',
            'phone' => 'required|string',
            'cell_phone' => 'required|string',

            // Address
            'zip_code' => 'required',
            'street' => 'required',
            'number' => 'required',
            'complement' => 'nullable',
            'neighborhood' => 'required',
            'city' => 'required',
            'state' => 'required',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'O tipo é obrigatório.',
            'type.in' => 'O tipo deve ser Pessoa Física ou Pessoa Jurídica.',
            'name_corporatereason.required_if' => 'O nome ou razão social é obrigatório quando o tipo é Pessoa Jurídica.',
            'name_corporatereason.max' => 'O nome ou razão social deve ter no máximo 255 caracteres.',
            'fantasy_name.max' => 'O nome fantasia deve ter no máximo 255 caracteres.',
            'cpf_cnpj.required' => 'O CPF/CNPJ é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'O e-mail deve ser um endereço de e-mail válido.',
            'email.max' => 'O e-mail deve ter no máximo 255 caracteres.',
            'phone.required' => 'O telefone é obrigatório.',
            'cell_phone.required' => 'O celular é obrigatório.',
            'zip_code.required' => 'O CEP é obrigatório.',
            'street.required' => 'A rua é obrigatória.',
            'number.required' => 'O número é obrigatório.',
            'neighborhood.required' => 'O bairro é obrigatório.',
            'city.required' => 'A cidade é obrigatória.',
            'state.required' => 'O estado é obrigatório.',
        ];
    }
}
