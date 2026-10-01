<?php

namespace App\Services;

use App\Enums\CalculationType;
use App\Enums\FeeDiscount;
use App\Enums\FinancingSystem;
use App\Enums\SupportedState;
use App\Models\FeeCalculation;
use App\Models\ItbiMunicipality;
use App\Models\Tenant;
use App\Services\FeeCalculator\FeeBreakdown;

class FeeCalculationService
{
    /**
     * Cálculo ainda válido (cálculos expirados deixam de existir para a UI).
     */
    public function findById(string $id, Tenant $tenant): FeeCalculation
    {
        return $tenant->run(fn () => FeeCalculation::notExpired()->findOrFail($id));
    }

    public function breakdown(FeeCalculation $calculation): FeeBreakdown
    {
        return FeeBreakdown::fromApiResult($calculation->api_result, $calculation->itbi_amount);
    }

    /**
     * Dados do formulário de cálculo, compartilhados pela tela de cálculo e
     * pela de resultado (que exibe o formulário preenchido para recalcular).
     *
     * @return array{type: array{value: int, label: string, description: string}, state: ?string, municipality: array{ibge_code: ?int, name: ?string, itbi_module: ?string}, financingSystems: array<int, array<string, mixed>>, discounts: array<int, array<string, mixed>>, commonNotes: string}
     */
    public function formProps(CalculationType $type, ?SupportedState $state, ?int $municipalityIbgeCode, ?string $municipalityName, Tenant $tenant): array
    {
        return [
            'type' => ['value' => $type->value, 'label' => $type->label(), 'description' => $type->description()],
            'state' => $state?->value,
            'municipality' => [
                'ibge_code' => $municipalityIbgeCode,
                'name' => $municipalityName,
                'itbi_module' => $municipalityIbgeCode
                    ? $tenant->run(fn () => ItbiMunicipality::where('ibge_code', $municipalityIbgeCode)->first()?->module?->value)
                    : null,
            ],
            'financingSystems' => FinancingSystem::options(),
            'discounts' => FeeDiscount::options(),
            'commonNotes' => __('fee_calculator.common_notes', [], 'pt_BR'),
        ];
    }

    /**
     * Resumo do cálculo para as telas de resultado, vínculo e orçamento.
     *
     * @return array<string, mixed>
     */
    public function present(FeeCalculation $calculation): array
    {
        $discount = FeeDiscount::tryFrom((string) ($calculation->input['discount'] ?? ''));

        return [
            'id' => $calculation->id,
            'type' => ['value' => $calculation->type->value, 'label' => $calculation->type->label()],
            'state' => $calculation->state->value,
            'municipality_name' => $calculation->municipality_name,
            'input' => $calculation->input,
            'discount' => $discount?->label(),
            'expires_at' => $calculation->expires_at->toIso8601String(),
            'breakdown' => $this->breakdown($calculation)->toArray(),
        ];
    }
}
