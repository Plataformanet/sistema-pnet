<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreDriveRequest extends FormRequest
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
            'user_id' => ['required'],
            'folder_id' => ['required'],
            'modified_by.*' => ['sometimes'],
            'modified_at.*' => ['sometimes'],
            'documents.*' => [
                'required',
                File::types(['doc', 'docx', 'pdf', 'jpg', 'jpeg', 'png', 'bmp', 'zip', 'txt', 'xlsx']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'documents.*.mimes' => 'O documento deve conter uma extensão válida',
            'documents.*.max' => 'O tamanho do documento tem que ser no máximo 3GB',
        ];
    }
}
