<?php

namespace App\Http\Requests;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Edição dos dados da proposta. Proponentes e vendedores não são alterados
 * aqui; o status aceito é só o escolhível manualmente.
 */
class UpdateProposalRequest extends StoreProposalRequest
{
    private ?Proposal $proposal = null;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'property' => filled($this->input('property.property_type_id')) ? $this->input('property') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $manual = array_map(fn (ProposalStatus $status) => $status->value, ProposalStatus::manuallySelectable());

        return array_merge($this->proposalRules(), [
            'status' => ['required', Rule::in([...$manual, ProposalStatus::FINISHED->value])],
            'cancellation_reason' => ['nullable', 'required_if:status,'.ProposalStatus::CANCELED->value, 'string'],
            'restriction_reason' => ['nullable', 'required_if:status,'.ProposalStatus::RESTRICTED->value, 'string'],
            'expected_delivery_month' => ['nullable', 'required_if:status,'.ProposalStatus::AWAITING_PROPERTY->value, 'integer', 'between:1,12'],
            'expected_delivery_year' => ['nullable', 'required_if:status,'.ProposalStatus::AWAITING_PROPERTY->value, 'integer', 'between:2000,2100'],
        ]);
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator) {
                if ($this->input('status') === ProposalStatus::FINISHED->value
                    && $this->proposal()?->status !== ProposalStatus::FINISHED) {
                    $validator->errors()->add('status', 'A proposta só é finalizada ao concluir a última etapa do acompanhamento.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'status.required' => 'Selecione o status da proposta.',
            'status.in' => 'O status selecionado não pode ser escolhido manualmente.',
            'cancellation_reason.required_if' => 'Informe o motivo do cancelamento.',
            'restriction_reason.required_if' => 'Informe o motivo da restrição.',
            'expected_delivery_month.required_if' => 'Informe o mês previsto de entrega do imóvel.',
            'expected_delivery_month.between' => 'O mês previsto deve estar entre 1 e 12.',
            'expected_delivery_year.required_if' => 'Informe o ano previsto de entrega do imóvel.',
            'expected_delivery_year.between' => 'O ano previsto é inválido.',
        ]);
    }

    protected function currentValue(string $field): int|string|null
    {
        $proposal = $this->proposal();

        return match ($field) {
            'creator_id' => $proposal?->creator_id,
            'analyst_id' => $proposal?->analyst_id,
            'bank_id' => $proposal?->bank_id,
            'contract_type_id' => $proposal?->contract_type_id,
            'property.property_type_id' => $proposal?->property?->property_type_id,
            'property.development_id' => $proposal?->property?->development_id,
            default => null,
        };
    }

    /**
     * @return array<int, int>
     */
    protected function currentPartnerIds(): array
    {
        return $this->proposal()?->partners->modelKeys() ?? [];
    }

    /**
     * Na edição o criador nunca é trocado automaticamente pelo usuário logado.
     */
    protected function restrictedActorId(): ?int
    {
        return null;
    }

    private function proposal(): ?Proposal
    {
        $id = $this->route('id');

        return $this->proposal ??= $id !== null ? Proposal::with(['property', 'partners:id'])->find($id) : null;
    }
}
