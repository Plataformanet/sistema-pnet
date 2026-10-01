<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadCostItemProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('bucket.proposal_document_max_kb')],
        ];
    }

    public function messages(): array
    {
        return [
            'proof.required' => 'Selecione o comprovante.',
            'proof.mimes' => 'O comprovante deve ser PDF, JPG ou PNG.',
            'proof.max' => 'O comprovante excede o tamanho máximo permitido.',
        ];
    }
}
