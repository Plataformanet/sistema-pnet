<?php

namespace App\Http\Requests;

use App\Enums\CalculationTypeEnum;
use App\Enums\FeeDiscountEnum;
use App\Enums\FinancingSystemEnum;
use App\Enums\ItbiModuleEnum;
use App\Enums\SupportedStateEnum;
use App\Models\ItbiMunicipality;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Valores em centavos (MoneyInput). Tipo e desconto precisam ser permitidos
 * para a UF escolhida.
 */
class CalculateFeesRequest extends FormRequest
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
        $isPurchase = fn () => (int) $this->input('type') === CalculationTypeEnum::PURCHASE_WITH_FIDUCIARY_LIEN->value;

        return [
            'state' => ['required', Rule::enum(SupportedStateEnum::class)],
            'municipality_ibge_code' => ['required', 'integer', 'digits:7'],
            'municipality_name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(CalculationTypeEnum::class)],
            'property_value' => ['required', 'integer', 'gt:0'],
            'financing_value' => [Rule::requiredIf($isPurchase), 'nullable', 'integer', 'min:0', 'lte:property_value'],
            'financing_system' => [Rule::requiredIf($isPurchase), 'nullable', Rule::enum(FinancingSystemEnum::class)],
            'first_property' => [Rule::requiredIf(fn () => $isPurchase() && $this->isFirstPropertyMunicipality()), 'nullable', 'boolean'],
            'discount' => ['nullable', Rule::enum(FeeDiscountEnum::class)],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $state = SupportedStateEnum::tryFrom((string) $this->input('state'));
                $type = CalculationTypeEnum::tryFrom((int) $this->input('type'));

                if ($state === null || $type === null) {
                    return;
                }

                if (! in_array($type, $state->allowedCalculationTypes(), true)) {
                    $validator->errors()->add('type', 'Este tipo de cálculo não está disponível para '.$state->label().'.');
                }

                $discount = FeeDiscountEnum::tryFrom((string) $this->input('discount'));

                if ($discount !== null && ! $discount->isAllowedFor($state)) {
                    $validator->errors()->add('discount', 'Este desconto não se aplica a '.$state->label().'.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'state.required' => 'Selecione o estado.',
            'state.enum' => 'Estado não atendido pela calculadora.',
            'municipality_ibge_code.required' => 'Selecione o município.',
            'municipality_ibge_code.digits' => 'Código IBGE do município inválido.',
            'municipality_name.required' => 'Selecione o município.',
            'type.required' => 'Selecione o tipo de cálculo.',
            'type.enum' => 'Tipo de cálculo inválido.',
            'property_value.required' => 'O Valor do imóvel / Transação é obrigatório!',
            'property_value.integer' => 'O Valor do imóvel / Transação é inválido!',
            'property_value.gt' => 'O Valor do imóvel / Transação deve ser maior que zero!',
            'financing_value.required' => 'O Financiamento é obrigatório!',
            'financing_value.integer' => 'O Financiamento é inválido!',
            'financing_value.lte' => 'O Financiamento não pode ser maior que o valor do imóvel!',
            'financing_system.required' => 'Selecione SFH ou SFI.',
            'first_property.required' => 'Informe se é o primeiro imóvel.',
            'discount.enum' => 'Desconto inválido.',
        ];
    }

    private function isFirstPropertyMunicipality(): bool
    {
        return ItbiMunicipality::where('ibge_code', $this->input('municipality_ibge_code'))
            ->where('module', ItbiModuleEnum::FIRST_PROPERTY_RATE->value)
            ->exists();
    }
}
