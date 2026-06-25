<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccessPermissionDriveRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'users' => ['required_if:permission,2'],
            'drive_id' => ['required'],
            'permission' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'users.required' => 'A escolha do usuário é obrigatória.',
            'permission.required' => 'Selecione a permissão para o usuário.',
            'users.required_if' => 'A escolha do usuário é obrigatória.',
        ];
    }
}
