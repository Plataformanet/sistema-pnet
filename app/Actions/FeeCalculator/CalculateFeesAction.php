<?php

namespace App\Actions\FeeCalculator;

use App\Enums\CalculationTypeEnum;
use App\Enums\FeeDiscountEnum;
use App\Enums\FinancingSystemEnum;
use App\Enums\SupportedStateEnum;
use App\Exceptions\Itbi\ItbiNotConfiguredException;
use App\Exceptions\RegistryFee\RegistryFeeRejectedException;
use App\Exceptions\RegistryFee\RegistryFeeUnavailableException;
use App\Models\FeeCalculation;
use App\Models\ItbiMunicipality;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Itbi\ItbiCalculator;
use App\Services\Itbi\ItbiInputDto;
use App\Services\RegistryFee\RegistryFeeApiClient;
use App\Services\RegistryFee\RegistryFeeRequestDto;

/**
 * Calcula os emolumentos (API externa) e o ITBI (local) e grava o retrato
 * imutável do cálculo. O ITBI é calculado antes da chamada paga à API: um
 * município sem cadastro falha sem consumir a API.
 */
class CalculateFeesAction
{
    public function __construct(
        protected RegistryFeeApiClient $registryFeeApiClient,
        protected ItbiCalculator $itbiCalculator,
    ) {}

    /**
     * @param  array<string, mixed>  $data  dados validados por CalculateFeesRequest (valores em centavos)
     *
     * @throws ItbiNotConfiguredException
     * @throws RegistryFeeRejectedException
     * @throws RegistryFeeUnavailableException
     */
    public function handle(array $data, User $user, Tenant $tenant): FeeCalculation
    {
        return $tenant->run(function () use ($data, $user) {
            $type = CalculationTypeEnum::from((int) $data['type']);
            $state = SupportedStateEnum::from($data['state']);
            $financingSystem = FinancingSystemEnum::tryFrom((string) ($data['financing_system'] ?? '')) ?? FinancingSystemEnum::SFH;
            $discount = FeeDiscountEnum::tryFrom((string) ($data['discount'] ?? ''));
            $financingValue = $type->requiresFinancing() ? (int) ($data['financing_value'] ?? 0) : 0;

            $itbi = $this->itbiCalculator->calculate(new ItbiInputDto(
                type: $type,
                propertyValue: (int) $data['property_value'],
                municipality: $type->hasItbi()
                    ? ItbiMunicipality::with(['rate', 'brackets'])->firstWhere('ibge_code', $data['municipality_ibge_code'])
                    : null,
                financingSystem: $financingSystem,
                financedValue: $financingValue,
                firstProperty: (bool) ($data['first_property'] ?? false),
            ));

            $response = $this->registryFeeApiClient->calculate(new RegistryFeeRequestDto(
                type: $type,
                state: $state,
                municipalityIbgeCode: (int) $data['municipality_ibge_code'],
                propertyValue: (int) $data['property_value'],
                financingValue: $financingValue,
                discount: $discount,
            ));

            return FeeCalculation::create([
                'user_id' => $user->id,
                'type' => $type,
                'state' => $state,
                'municipality_ibge_code' => (int) $data['municipality_ibge_code'],
                'municipality_name' => $data['municipality_name'],
                'input' => [
                    'property_value' => (int) $data['property_value'],
                    'financing_value' => $financingValue,
                    'financing_system' => $type->requiresFinancing() ? $financingSystem->value : null,
                    'first_property' => (bool) ($data['first_property'] ?? false),
                    'discount' => $discount?->value,
                ],
                'api_result' => $response['raw'],
                'fees_total' => $response['result']->total,
                'itbi_amount' => $itbi,
                'expires_at' => now()->addDays(FeeCalculation::LIFETIME_DAYS),
            ]);
        });
    }
}
