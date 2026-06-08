<?php

namespace App\Http\Requests;

class UpdateAccountReceivableRequest extends StoreAccountReceivableRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'installments' => 'nullable|array',
            'installments.*.installment_id' => 'required|integer|exists:installments,id',
            'installments.*.value' => 'required',
            'installments.*.due_date' => 'required|date',
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'installments.array' => 'As parcelas devem ser uma lista válida.',
            'installments.*.installment_id.required' => 'A parcela é obrigatória.',
            'installments.*.installment_id.integer' => 'A parcela é inválida.',
            'installments.*.installment_id.exists' => 'A parcela selecionada não existe.',
            'installments.*.value.required' => 'O valor da parcela é obrigatório.',
            'installments.*.due_date.required' => 'A data de vencimento da parcela é obrigatória.',
            'installments.*.due_date.date' => 'A data de vencimento da parcela é inválida.',
        ]);
    }
}
