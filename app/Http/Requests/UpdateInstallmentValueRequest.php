<?php

namespace App\Http\Requests;

use App\Utils\Utils;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInstallmentValueRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'id'    => 'required',
            'value' => 'required'
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'value' => Utils::format_coin_sql($this->value),
        ]);
    }

    public function messages(): array
    {
        return [
            'value.required' => 'O valor da parcela é obrigatório.',
        ];
    }
}
