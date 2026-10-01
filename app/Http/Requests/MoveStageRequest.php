<?php

namespace App\Http\Requests;

use App\Services\StageService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveStageRequest extends FormRequest
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
            'direction' => ['required', Rule::in([StageService::DIRECTION_UP, StageService::DIRECTION_DOWN])],
        ];
    }

    public function messages(): array
    {
        return [
            'direction.required' => 'Informe a direção da movimentação.',
            'direction.in' => 'A direção da movimentação é inválida.',
        ];
    }
}
