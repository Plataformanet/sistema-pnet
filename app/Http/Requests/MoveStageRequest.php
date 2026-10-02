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
            'direction' => ['required_without:target_id', 'nullable', Rule::in([StageService::DIRECTION_UP, StageService::DIRECTION_DOWN])],
            'target_id' => ['required_without:direction', 'nullable', 'integer', Rule::exists('stages', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'direction.required_without' => 'Informe a direção da movimentação.',
            'direction.in' => 'A direção da movimentação é inválida.',
            'target_id.required_without' => 'Informe a etapa de destino.',
            'target_id.integer' => 'A etapa de destino é inválida.',
            'target_id.exists' => 'A etapa de destino não existe ou foi excluída.',
        ];
    }
}
