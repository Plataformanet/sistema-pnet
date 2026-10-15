<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FinishedProposalsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * O empreendimento é validado também entre os excluídos: o filtro precisa
     * encontrar propostas antigas de empreendimentos que já saíram do cadastro.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date', 'required_with:end_date'],
            'end_date' => ['nullable', 'date', 'required_with:start_date', 'after_or_equal:start_date'],
            'development_id' => ['nullable', 'integer', 'exists:developments,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.date' => 'A data inicial é inválida.',
            'start_date.required_with' => 'Informe a data inicial do período.',
            'end_date.date' => 'A data final é inválida.',
            'end_date.required_with' => 'Informe a data final do período.',
            'end_date.after_or_equal' => 'A data final não pode ser anterior à data inicial.',
            'development_id.integer' => 'O empreendimento selecionado é inválido.',
            'development_id.exists' => 'O empreendimento selecionado não existe.',
            'page.integer' => 'A página informada é inválida.',
        ];
    }
}
