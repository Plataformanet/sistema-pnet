<?php

namespace App\Http\Requests;

use App\Models\CostType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Lançamento manual na aba Pagamentos e Taxas. O valor chega em centavos.
 */
class StoreProposalCostItemRequest extends FormRequest
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
            'cost_type_id' => ['required', 'integer', Rule::exists('cost_types', 'id')->withoutTrashed()],
            'notary_id' => ['nullable', 'integer', Rule::exists('notaries', 'id')->withoutTrashed()],
            'description' => ['required', 'string', 'max:191'],
            'date' => ['nullable', 'date'],
            'amount' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'bill' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('bucket.proposal_document_max_kb')],
            'notify_partners' => ['boolean'],
            'notify_applicants' => ['boolean'],
        ];
    }

    /**
     * Tipos de custo vinculados a cartório exigem o cartório (antes `habilita-cartorio.js`).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('cost_type_id') || filled($this->input('notary_id'))) {
                    return;
                }

                if (CostType::whereKey($this->input('cost_type_id'))->value('requires_notary')) {
                    $validator->errors()->add('notary_id', 'Este tipo de custo exige o cartório.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'cost_type_id.required' => 'Selecione o tipo de custo.',
            'cost_type_id.exists' => 'O tipo de custo selecionado não está disponível.',
            'notary_id.exists' => 'O cartório selecionado não está disponível.',
            'description.required' => 'A rubrica é obrigatória.',
            'description.max' => 'A rubrica deve ter no máximo 191 caracteres.',
            'date.date' => 'Informe uma data válida.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.integer' => 'O valor é inválido.',
            'amount.min' => 'O valor não pode ser negativo.',
            'bill.mimes' => 'O boleto deve ser PDF, JPG ou PNG.',
            'bill.max' => 'O boleto excede o tamanho máximo permitido.',
        ];
    }
}
