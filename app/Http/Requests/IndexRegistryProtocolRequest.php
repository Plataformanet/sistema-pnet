<?php

namespace App\Http\Requests;

use App\Enums\RegistryProtocolPositionEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRegistryProtocolRequest extends FormRequest
{
    public const STATUSES = ['open', 'closed', 'all'];

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
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'position' => ['nullable', Rule::enum(RegistryProtocolPositionEnum::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'proposal_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'A aba selecionada é inválida.',
            'position.enum' => 'A posição selecionada é inválida.',
            'search.max' => 'A busca deve ter no máximo 100 caracteres.',
            'proposal_id.integer' => 'A proposta informada é inválida.',
            'proposal_id.min' => 'A proposta informada é inválida.',
            'page.integer' => 'A página informada é inválida.',
        ];
    }
}
