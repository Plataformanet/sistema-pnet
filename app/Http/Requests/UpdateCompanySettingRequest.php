<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('settings.company.edit') ?? false;
    }

    /**
     * Prepara os dados para validação tratando valores booleanos do FormData.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('remove_logo')) {
            $this->merge([
                'remove_logo' => filter_var($this->input('remove_logo'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'ie' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2'],
            'logo' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,svg', 'mimetypes:image/jpeg,image/png,image/webp,image/svg+xml', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }
}
