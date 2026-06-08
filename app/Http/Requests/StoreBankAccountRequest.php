<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
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
            'name' => 'required|string|max:191',
            'bank' => 'required|string|max:191',
            'agency' => 'required|string|max:191',
            'account_number' => 'required|string|max:191',
            'account_type' => 'required|string|max:50',
            'initial_balance' => 'nullable|integer',
            'current_balance' => 'nullable|integer',
            'active' => 'sometimes',
            'main_account' => 'sometimes',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da conta é obrigatório.',
            'name.string' => 'O nome da conta deve ser um texto válido.',
            'name.max' => 'O nome da conta deve ter no máximo 191 caracteres.',
            'bank.required' => 'O banco é obrigatório.',
            'bank.string' => 'O banco deve ser um texto válido.',
            'bank.max' => 'O banco deve ter no máximo 191 caracteres.',
            'agency.required' => 'A agência é obrigatória.',
            'agency.string' => 'A agência deve ser um texto válido.',
            'agency.max' => 'A agência deve ter no máximo 191 caracteres.',
            'account_number.required' => 'O número da conta é obrigatório.',
            'account_number.string' => 'O número da conta deve ser um texto válido.',
            'account_number.max' => 'O número da conta deve ter no máximo 191 caracteres.',
            'account_type.required' => 'O tipo de conta é obrigatório.',
            'account_type.string' => 'O tipo de conta deve ser um texto válido.',
            'account_type.max' => 'O tipo de conta deve ter no máximo 50 caracteres.',
        ];
    }
}
