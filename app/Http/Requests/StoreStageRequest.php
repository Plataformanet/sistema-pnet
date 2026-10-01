<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => Str::squish((string) $this->input('name'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'order' => ['required', 'integer', 'min:1', 'max:65535', Rule::unique('stages', 'order')->withoutTrashed()->ignore($this->ignoreId())],
            'has_date' => ['required', 'boolean'],
            'date_required' => ['required', 'boolean'],
            'has_upload' => ['required', 'boolean'],
            'upload_required' => ['required', 'boolean'],
            'title_required' => ['required', 'boolean'],
            'notes_required' => ['required', 'boolean'],
            'shows_property_data' => ['required', 'boolean'],
            'shows_registry_protocol' => ['required', 'boolean'],
            'completion_deadline_hours' => ['required', 'integer', 'min:0'],
            'alert_deadline_hours' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Regras cruzadas entre os flags: um campo obrigatório que não é exibido
     * deixa a etapa impossível de concluir (caso "Saque de FGTS" do legado).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->boolean('date_required') && ! $this->boolean('has_date')) {
                    $validator->errors()->add('date_required', 'A data só pode ser obrigatória quando a etapa possui data.');
                }

                if ($this->boolean('upload_required') && ! $this->boolean('has_upload')) {
                    $validator->errors()->add('upload_required', 'O arquivo só pode ser obrigatório quando a etapa possui upload.');
                }

                if ($this->boolean('title_required') && ! $this->boolean('has_upload')) {
                    $validator->errors()->add('title_required', 'O título é do arquivo: só pode ser obrigatório quando a etapa possui upload.');
                }

                $completion = (int) $this->input('completion_deadline_hours');
                $alert = (int) $this->input('alert_deadline_hours');

                if ($completion > 0 && $alert > 0 && $alert > $completion) {
                    $validator->errors()->add('alert_deadline_hours', 'O prazo de alerta não pode ser maior que o prazo de conclusão.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da etapa precisa ser preenchido.',
            'name.max' => 'O nome da etapa deve ter no máximo 191 caracteres.',
            'order.required' => 'A ordem da etapa precisa ser preenchida.',
            'order.integer' => 'A ordem da etapa deve ser um número inteiro.',
            'order.min' => 'A ordem da etapa deve ser maior que zero.',
            'order.max' => 'A ordem da etapa é muito alta.',
            'order.unique' => 'Já existe uma etapa ativa com esta ordem.',
            'completion_deadline_hours.required' => 'O prazo de conclusão precisa ser preenchido.',
            'completion_deadline_hours.integer' => 'O prazo de conclusão deve ser em horas inteiras.',
            'completion_deadline_hours.min' => 'O prazo de conclusão não pode ser negativo.',
            'alert_deadline_hours.required' => 'O prazo de alerta precisa ser preenchido.',
            'alert_deadline_hours.integer' => 'O prazo de alerta deve ser em horas inteiras.',
            'alert_deadline_hours.min' => 'O prazo de alerta não pode ser negativo.',
            '*.required' => 'Este campo é obrigatório.',
            '*.boolean' => 'Este campo deve ser Sim ou Não.',
        ];
    }

    protected function ignoreId(): ?string
    {
        return null;
    }
}
