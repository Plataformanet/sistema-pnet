<?php

namespace App\Http\Requests;

use App\Enums\AccountsEnum;
use App\Enums\FinancialPaymentMethodEnum;
use App\Exceptions\InstallmentsTotalMismatchException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAccountReceivableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'financial_category_id' => ['required', 'integer', 'exists:financial_categories,id'],
            'financial_subcategory_id' => ['nullable', 'integer', 'exists:financial_subcategories,id'],
            'cost_id' => ['nullable', 'integer', 'exists:cost_types,id'],
            'bank_account_id' => ['required', 'integer', Rule::exists('bank_accounts', 'id')->whereNull('deleted_at')],
            'financial_contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'description' => ['required', 'string'],
            'total' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', Rule::enum(FinancialPaymentMethodEnum::class)],
            'payment_condition' => ['required', 'string', Rule::in(self::paymentConditions())],
            'total_installments' => ['required', 'integer'],
            'bank_account_out' => ['required', 'integer'],
            'observations' => ['nullable', 'string'],
            'receipt' => ['nullable', 'string'],

            // Parcelas (installments)
            'value' => ['required', 'integer', 'min:1'],
            'due_date' => ['required', 'date'],
            'status' => ['required', Rule::enum(AccountsEnum::class)->only([AccountsEnum::OPEN, AccountsEnum::PAID])],

            'installments' => ['nullable', 'array'],
            'installments.*.installment_id' => ['nullable', 'integer'],
            'installments.*.value' => ['required', 'integer', 'min:1'],
            'installments.*.due_date' => ['required', 'date'],
        ];
    }

    /**
     * Garante que as parcelas enviadas somam exatamente o total do título.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty() || empty($this->input('installments'))) {
                    return;
                }

                $installmentsTotal = (int) collect($this->input('installments'))->sum('value');
                $total = (int) $this->input('total');

                if ($installmentsTotal !== $total) {
                    $validator->errors()->add('installments', (new InstallmentsTotalMismatchException($installmentsTotal, $total))->getMessage());
                }
            },
        ];
    }

    /**
     * Condições aceitas pelo formulário: "a-vista" ou de 1 a 12 parcelas.
     *
     * @return list<string>
     */
    public static function paymentConditions(): array
    {
        return ['a-vista', ...array_map('strval', range(1, 12))];
    }

    public function messages(): array
    {
        return [
            'financial_category_id.required' => 'A categoria financeira é obrigatória.',
            'financial_category_id.integer' => 'A categoria financeira é inválida.',
            'financial_category_id.exists' => 'A categoria financeira selecionada não existe.',
            'financial_subcategory_id.integer' => 'A subcategoria financeira é inválida.',
            'financial_subcategory_id.exists' => 'A subcategoria financeira selecionada não existe.',
            'cost_id.integer' => 'O custo é inválido.',
            'cost_id.exists' => 'O custo selecionado não existe.',
            'bank_account_id.required' => 'A conta bancária é obrigatória.',
            'bank_account_id.integer' => 'A conta bancária é inválida.',
            'bank_account_id.exists' => 'A conta bancária selecionada não existe.',
            'financial_contact_id.required' => 'O contato é obrigatório.',
            'financial_contact_id.integer' => 'O contato é inválido.',
            'financial_contact_id.exists' => 'O contato selecionado não existe.',
            'description.required' => 'A descrição é obrigatória.',
            'description.string' => 'A descrição deve ser um texto válido.',
            'total.required' => 'O valor total é obrigatório.',
            'total.integer' => 'O total deve ser um valor inteiro.',
            'total.min' => 'O valor total deve ser maior que zero.',
            'payment_method.required' => 'A forma de pagamento é obrigatória.',
            'payment_method.enum' => 'A forma de pagamento é inválida.',
            'payment_condition.required' => 'A condição de pagamento é obrigatória.',
            'payment_condition.string' => 'A condição de pagamento deve ser um texto válido.',
            'payment_condition.in' => 'A condição de pagamento selecionada é inválida.',
            'total_installments.required' => 'O total de parcelas é obrigatório.',
            'total_installments.integer' => 'O total de parcelas deve ser um valor inteiro.',
            'bank_account_out.required' => 'A conta de saída é obrigatória.',
            'bank_account_out.integer' => 'A conta de saída é inválida.',
            'observations.string' => 'As observações devem ser um texto válido.',
            'receipt.string' => 'O comprovante deve ser um texto válido.',
            'value.required' => 'O valor é obrigatório.',
            'value.integer' => 'O valor deve ser um valor inteiro.',
            'value.min' => 'O valor deve ser maior que zero.',
            'due_date.required' => 'A data de vencimento é obrigatória.',
            'due_date.date' => 'A data de vencimento é inválida.',
            'status.required' => 'O status é obrigatório.',
            'status.enum' => 'O status selecionado é inválido.',

            'installments.array' => 'As parcelas devem ser uma lista válida.',
            'installments.*.value.required' => 'O valor da parcela é obrigatório.',
            'installments.*.value.integer' => 'O valor da parcela deve ser um valor inteiro.',
            'installments.*.value.min' => 'O valor da parcela deve ser maior que zero.',
            'installments.*.due_date.required' => 'A data de vencimento da parcela é obrigatória.',
            'installments.*.due_date.date' => 'A data de vencimento da parcela é inválida.',
        ];
    }
}
