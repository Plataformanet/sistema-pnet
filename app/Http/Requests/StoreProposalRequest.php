<?php

namespace App\Http\Requests;

use App\Enums\AmortizationTable;
use App\Enums\BankAccountType;
use App\Enums\MaritalStatus;
use App\Enums\PersonType;
use App\Enums\PropertyCondition;
use App\Enums\RolesEnum;
use App\Models\ContractType;
use App\Models\PropertyType;
use App\Rules\CnpjRule;
use App\Rules\CpfRule;
use App\Rules\UserHasRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

/**
 * Valores monetários chegam em centavos inteiros. Proponentes e vendedores são
 * montados no formulário e enviados juntos com a proposta.
 */
class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'applicants' => collect($this->input('applicants', []))
                ->map(fn (array $applicant) => $this->withoutEmptyBankAccount(array_merge($applicant, [
                    'cpf' => preg_replace('/\D/', '', (string) ($applicant['cpf'] ?? '')),
                ])))
                ->all(),
            'sellers' => collect($this->input('sellers', []))
                ->map(fn (array $seller) => $this->withoutEmptyBankAccount(array_merge($seller, [
                    'document' => preg_replace('/\D/', '', (string) ($seller['document'] ?? '')),
                ])))
                ->all(),
            'property' => filled($this->input('property.property_type_id')) ? $this->input('property') : null,
        ]);

        $restrictedActorId = $this->restrictedActorId();

        if ($restrictedActorId !== null) {
            $this->merge(['creator_id' => $restrictedActorId]);
        }
    }

    /**
     * Quem só tem cargos externos (ex.: Parceiro) vira o criador da proposta
     * que cadastra: é pelo `creator_id` que ele continua enxergando a
     * proposta (`Proposal::scopeVisibleTo`).
     */
    protected function restrictedActorId(): ?int
    {
        $user = $this->user();

        return $user !== null && $user->hasOnlyProposalRestrictedRoles() ? $user->id : null;
    }

    /**
     * Parceiros já vinculados à proposta em edição (aceitos mesmo sem o cargo).
     *
     * @return array<int, int>
     */
    protected function currentPartnerIds(): array
    {
        return [];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->proposalRules(), $this->peopleRules());
    }

    /**
     * Regras dos dados da proposta, compartilhadas com a edição.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function proposalRules(): array
    {
        $requiresFinancing = fn () => (bool) ContractType::whereKey($this->input('contract_type_id'))->value('requires_financing');

        return [
            'creator_id' => ['required', 'integer', new UserHasRole(
                RolesEnum::proposalStaffLabels(),
                'O criador precisa ser um administrador ou analista.',
                [$this->currentValue('creator_id'), $this->restrictedActorId()],
            )],
            'analyst_id' => ['nullable', 'integer', new UserHasRole(
                RolesEnum::proposalStaffLabels(),
                'O analista precisa ser um administrador ou analista.',
                [$this->currentValue('analyst_id')],
            )],
            'partner_ids' => ['nullable', 'array'],
            'partner_ids.*' => ['integer', new UserHasRole(
                [RolesEnum::PARTNER->label()],
                'Selecione apenas usuários com o cargo Parceiro.',
                $this->currentPartnerIds(),
            )],
            'bank_id' => ['required', 'integer', $this->activeOrCurrent('banks', 'bank_id')],
            'contract_type_id' => ['required', 'integer', $this->activeOrCurrent('contract_types', 'contract_type_id')],
            'amortization_table' => [Rule::requiredIf($requiresFinancing), 'nullable', Rule::enum(AmortizationTable::class)],
            'payment_term' => [Rule::requiredIf($requiresFinancing), 'nullable', 'integer', 'min:1', 'max:600'],
            'property_condition' => ['required', Rule::enum(PropertyCondition::class)],
            'has_other_property' => ['boolean'],
            'purchase_value' => ['required', 'integer', 'min:0'],
            'down_payment_value' => ['required', 'integer', 'min:0'],
            'financing_value' => ['nullable', 'integer', 'min:0'],
            'expenses_value' => ['nullable', 'integer', 'min:0'],
            'subsidy_value' => ['nullable', 'integer', 'min:0'],
            'financed_value' => ['nullable', 'integer', 'min:0'],
            'intended_installment_value' => ['nullable', 'integer', 'min:0'],
            'fgts_value' => ['nullable', 'integer', 'min:0'],
            'uses_fgts' => ['boolean'],
            'is_first_financing' => ['boolean'],
            'finance_documentation_fee' => ['boolean'],
            'documentation_fee_to_finance' => ['nullable', 'integer', 'min:0'],
            'declares_income_tax' => ['boolean'],
            'declared_income' => ['nullable', 'integer', 'min:0'],
            'contract_notes' => ['nullable', 'string'],
            'purchase_value_notes' => ['nullable', 'string'],
            'down_payment_notes' => ['nullable', 'string'],
            'fgts_notes' => ['nullable', 'string'],
            'documentation_fee_notes' => ['nullable', 'string'],
            'documentation_financing_notes' => ['nullable', 'string'],
            'income_tax_notes' => ['nullable', 'string'],
            'general_notes' => ['nullable', 'string'],
            'property' => ['nullable', 'array'],
            'property.property_type_id' => ['required_with:property', 'integer', $this->activeOrCurrent('property_types', 'property.property_type_id')],
            'property.development_id' => ['nullable', 'integer', $this->activeOrCurrent('developments', 'property.development_id')],
            'property.address' => ['nullable', 'string', 'max:191'],
            'property.number' => ['nullable', 'string', 'max:20'],
            'property.complement' => ['nullable', 'string', 'max:100'],
            'property.block' => ['nullable', 'string', 'max:50'],
            'property.unit' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Proponentes já existentes (reaproveitados pelo CPF) só precisam do CPF.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function peopleRules(): array
    {
        $newApplicant = 'exclude_if:applicants.*.existing,true';

        return [
            'applicants' => ['required', 'array', 'min:1'],
            'applicants.*.existing' => ['boolean'],
            'applicants.*.cpf' => ['required', 'distinct', new CpfRule],
            'applicants.*.name' => [$newApplicant, 'required', 'string', 'min:3', 'max:191'],
            'applicants.*.email' => [$newApplicant, 'required', 'email', 'max:191'],
            'applicants.*.phone' => [$newApplicant, 'required', 'string', 'max:20'],
            'applicants.*.declared_income' => [$newApplicant, 'required', 'integer', 'min:0'],
            'applicants.*.marital_status' => [$newApplicant, 'required', Rule::enum(MaritalStatus::class)],
            'applicants.*.profession' => [$newApplicant, 'required', 'string', 'max:191'],
            'applicants.*.birth_date' => [$newApplicant, 'nullable', 'date', 'before:today'],
            'applicants.*.family_income' => [$newApplicant, 'nullable', 'integer', 'min:0'],
            'applicants.*.declares_income_tax' => [$newApplicant, 'boolean'],
            'applicants.*.income_tax_notes' => [$newApplicant, 'nullable', 'string'],
            'applicants.*.by_power_of_attorney' => [$newApplicant, 'boolean'],
            'applicants.*.bank_account' => [$newApplicant, 'nullable', 'array'],
            'applicants.*.bank_account.bank_name' => [$newApplicant, 'required_with:applicants.*.bank_account', 'string', 'max:100'],
            'applicants.*.bank_account.account_type' => [$newApplicant, 'required_with:applicants.*.bank_account', Rule::enum(BankAccountType::class)],
            'applicants.*.bank_account.branch' => [$newApplicant, 'required_with:applicants.*.bank_account', 'string', 'max:20'],
            'applicants.*.bank_account.number' => [$newApplicant, 'required_with:applicants.*.bank_account', 'string', 'max:30'],
            'applicants.*.bank_account.notes' => [$newApplicant, 'nullable', 'string'],
            'sellers' => ['nullable', 'array'],
            'sellers.*.person_type' => ['required', Rule::enum(PersonType::class)],
            'sellers.*.document' => ['required', 'distinct'],
            'sellers.*.name' => ['required', 'string', 'min:3', 'max:191'],
            'sellers.*.email' => ['required', 'email', 'max:191'],
            'sellers.*.phone' => ['nullable', 'string', 'max:20'],
            'sellers.*.marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'sellers.*.profession' => ['nullable', 'string', 'max:191'],
            'sellers.*.declared_income' => ['nullable', 'integer', 'min:0'],
            'sellers.*.declares_income_tax' => ['boolean'],
            'sellers.*.income_tax_notes' => ['nullable', 'string'],
            'sellers.*.by_power_of_attorney' => ['boolean'],
            'sellers.*.bank_account' => ['nullable', 'array'],
            'sellers.*.bank_account.bank_name' => ['required_with:sellers.*.bank_account', 'string', 'max:100'],
            'sellers.*.bank_account.account_type' => ['required_with:sellers.*.bank_account', Rule::enum(BankAccountType::class)],
            'sellers.*.bank_account.branch' => ['required_with:sellers.*.bank_account', 'string', 'max:20'],
            'sellers.*.bank_account.number' => ['required_with:sellers.*.bank_account', 'string', 'max:30'],
            'sellers.*.bank_account.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $propertyTypeId = $this->input('property.property_type_id');

                if ($propertyTypeId && blank($this->input('property.development_id'))
                    && PropertyType::whereKey($propertyTypeId)->value('requires_development')) {
                    $validator->errors()->add('property.development_id', 'Este tipo de imóvel exige o empreendimento.');
                }

                foreach ($this->input('sellers', []) as $index => $seller) {
                    $rule = ($seller['person_type'] ?? null) === PersonType::COMPANY->value ? new CnpjRule : new CpfRule;
                    $check = ValidatorFacade::make(['document' => $seller['document'] ?? ''], ['document' => [$rule]]);

                    if ($check->fails()) {
                        $validator->errors()->add("sellers.{$index}.document", $check->errors()->first('document'));
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'creator_id.required' => 'Selecione o criador da proposta.',
            'bank_id.required' => 'Selecione o banco.',
            'bank_id.exists' => 'O banco selecionado não está disponível.',
            'contract_type_id.required' => 'Selecione o tipo de contrato.',
            'amortization_table.required' => 'Selecione a tabela de amortização: o contrato exige dados de financiamento.',
            'payment_term.required' => 'Informe o prazo: o contrato exige dados de financiamento.',
            'payment_term.integer' => 'O prazo deve ser informado em meses.',
            'payment_term.max' => 'O prazo não pode passar de 600 meses.',
            'property_condition.required' => 'Informe se o imóvel é novo ou usado.',
            'purchase_value.required' => 'O valor de compra é obrigatório.',
            'down_payment_value.required' => 'O valor de entrada é obrigatório.',
            '*.integer' => 'Valor inválido.',
            '*.min' => 'O valor não pode ser negativo.',
            'property.property_type_id.required_with' => 'Selecione o tipo de imóvel.',
            'applicants.required' => 'Adicione ao menos um proponente.',
            'applicants.min' => 'Adicione ao menos um proponente.',
            'applicants.*.cpf.required' => 'O CPF do proponente é obrigatório.',
            'applicants.*.cpf.distinct' => 'Este CPF foi adicionado mais de uma vez.',
            'applicants.*.name.required' => 'O nome do proponente é obrigatório.',
            'applicants.*.name.min' => 'O nome do proponente deve ter no mínimo 3 caracteres.',
            'applicants.*.email.required' => 'O e-mail do proponente é obrigatório.',
            'applicants.*.email.email' => 'Informe um e-mail válido.',
            'applicants.*.phone.required' => 'O telefone do proponente é obrigatório.',
            'applicants.*.declared_income.required' => 'A renda declarada é obrigatória.',
            'applicants.*.marital_status.required' => 'Selecione o estado civil.',
            'applicants.*.marital_status.enum' => 'O estado civil selecionado é inválido.',
            'applicants.*.profession.required' => 'A profissão é obrigatória.',
            'applicants.*.birth_date.before' => 'A data de nascimento deve ser anterior a hoje.',
            'applicants.*.bank_account.*.required_with' => 'Preencha todos os dados da conta bancária.',
            'sellers.*.person_type.required' => 'Selecione se o vendedor é pessoa física ou jurídica.',
            'sellers.*.document.required' => 'O CPF/CNPJ do vendedor é obrigatório.',
            'sellers.*.document.distinct' => 'Este documento foi adicionado mais de uma vez.',
            'sellers.*.name.required' => 'O nome do vendedor é obrigatório.',
            'sellers.*.name.min' => 'O nome do vendedor deve ter no mínimo 3 caracteres.',
            'sellers.*.email.required' => 'O e-mail do vendedor é obrigatório.',
            'sellers.*.email.email' => 'Informe um e-mail válido.',
            'sellers.*.bank_account.*.required_with' => 'Preencha todos os dados da conta bancária.',
        ];
    }

    /**
     * Cadastro excluído não pode ser escolhido, mas o valor já gravado na
     * proposta em edição continua válido.
     */
    protected function activeOrCurrent(string $table, string $field): Exists
    {
        $current = $this->currentValue($field);

        return Rule::exists($table, 'id')->where(fn ($query) => $query->where(
            fn ($query) => $query->whereNull('deleted_at')->when($current, fn ($query) => $query->orWhere('id', $current))
        ));
    }

    /**
     * Valor gravado na proposta em edição (nulo na criação).
     */
    protected function currentValue(string $field): int|string|null
    {
        return null;
    }

    /**
     * @param  array<string, mixed>  $person
     * @return array<string, mixed>
     */
    private function withoutEmptyBankAccount(array $person): array
    {
        $account = $person['bank_account'] ?? null;

        if (is_array($account) && collect($account)->filter(fn ($value) => filled($value))->isEmpty()) {
            $person['bank_account'] = null;
        }

        return $person;
    }
}
