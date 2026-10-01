<?php

namespace App\Http\Requests;

use App\Enums\ItbiModule;
use App\Enums\SupportedState;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Nome e código IBGE vêm do seletor de município (API do IBGE), sem digitação.
 */
class StoreItbiMunicipalityRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'state' => ['required', Rule::enum(SupportedState::class)],
            'ibge_code' => ['required', 'integer', 'digits:7', Rule::unique('itbi_municipalities', 'ibge_code')->ignore($this->route('id'))],
            'module' => ['required', Rule::enum(ItbiModule::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Selecione o município.',
            'state.required' => 'Selecione o estado.',
            'state.enum' => 'Estado não atendido pela calculadora.',
            'ibge_code.required' => 'Selecione o município.',
            'ibge_code.digits' => 'Código IBGE inválido.',
            'ibge_code.unique' => 'Este município já possui cadastro de ITBI.',
            'module.required' => 'Selecione o módulo de cálculo.',
            'module.enum' => 'Módulo de cálculo inválido.',
        ];
    }
}
