<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDriveRequest extends FormRequest
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
            'id' => ['required'],
            'type_drive' => ['required'],
            'name' => ['required'],
            'drive_type' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_drive.required' => 'O tipo do registro (pasta ou arquivo) é obrigatório',
            'name.required' => 'O nome do documento ou da pasta deve ser informado',
            'drive_type.required' => 'O tipo do drive é obrigatório',
        ];
    }
}
