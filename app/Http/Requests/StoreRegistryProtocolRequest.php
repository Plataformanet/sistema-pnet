<?php

namespace App\Http\Requests;

use App\Enums\RegistryProtocolPositionEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistryProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect(['protocol_number', 'buyer_name', 'seller_name'])
            ->filter(fn (string $field) => is_string($this->input($field)))
            ->mapWithKeys(fn (string $field) => [$field => trim($this->input($field)) ?: null])
            ->all());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currentProposalId = $this->currentProposalId();

        return [
            'proposal_id' => [
                'nullable',
                'integer',
                Rule::exists('proposals', 'id')->where(fn (Builder $query) => $query
                    ->whereNull('deleted_at')
                    ->when($currentProposalId, fn (Builder $query, int $id) => $query->orWhere('id', $id))),
            ],
            'protocol_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('registry_protocols', 'protocol_number')->withoutTrashed()->ignore($this->ignoreId()),
            ],
            'buyer_name' => ['required', 'string', 'min:3', 'max:255'],
            'seller_name' => ['nullable', 'string', 'max:255'],
            'entered_at' => ['required', 'date'],
            'exited_at' => ['nullable', 'date', 'after_or_equal:entered_at'],
            'position' => ['required', Rule::enum(RegistryProtocolPositionEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'proposal_id.integer' => 'A proposta selecionada é inválida.',
            'proposal_id.exists' => 'A proposta selecionada não existe ou foi excluída.',
            'protocol_number.required' => 'O número do protocolo precisa ser preenchido.',
            'protocol_number.max' => 'O número do protocolo deve ter no máximo 50 caracteres.',
            'protocol_number.unique' => 'Já existe um protocolo com este número.',
            'buyer_name.required' => 'O nome do comprador precisa ser preenchido.',
            'buyer_name.min' => 'O nome do comprador deve ter pelo menos 3 caracteres.',
            'buyer_name.max' => 'O nome do comprador deve ter no máximo 255 caracteres.',
            'seller_name.max' => 'O nome do vendedor deve ter no máximo 255 caracteres.',
            'entered_at.required' => 'A data de entrada precisa ser preenchida.',
            'entered_at.date' => 'A data de entrada é inválida.',
            'exited_at.date' => 'A data de saída é inválida.',
            'exited_at.after_or_equal' => 'A data de saída não pode ser anterior à data de entrada.',
            'position.required' => 'A posição precisa ser selecionada.',
            'position.enum' => 'A posição selecionada é inválida.',
        ];
    }

    protected function ignoreId(): ?string
    {
        return null;
    }

    /**
     * Proposta já gravada no protocolo em edição: continua aceita mesmo que
     * tenha sido excluída depois, para não travar a edição do protocolo.
     */
    protected function currentProposalId(): ?int
    {
        return null;
    }
}
