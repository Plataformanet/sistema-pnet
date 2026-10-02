<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import FieldError from "@/components/ui/field/FieldError.vue";
import MoneyInput from "@/components/MoneyInput.vue";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import ApplicantList from "./ApplicantList.vue";
import SellerList from "./SellerList.vue";
import PropertyFields from "./PropertyFields.vue";
import StatusFields from "./StatusFields.vue";
import { computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import type { ProposalFormOptions, StatusOption } from "@/types";

const props = withDefaults(
    defineProps<{
        form: ReturnType<typeof useForm>;
        options: ProposalFormOptions;
        submitText?: string;
        withPeople?: boolean;
        statuses?: StatusOption[];
        finishedAt?: string | null;
        readonly?: boolean;
    }>(),
    {
        submitText: "Salvar Proposta",
        withPeople: true,
        statuses: () => [],
        finishedAt: null,
        readonly: false,
    },
);

const emit = defineEmits(["submit"]);

const requiresFinancing = computed(
    () =>
        props.options.contractTypes.find((type) => type.id === Number(props.form.contract_type_id))
            ?.requires_financing ?? false,
);

const moneyFields: Array<{ name: string; label: string; required?: boolean }> = [
    { name: "purchase_value", label: "Valor de compra", required: true },
    { name: "down_payment_value", label: "Valor de entrada", required: true },
    { name: "financing_value", label: "Valor do financiamento" },
    { name: "financed_value", label: "Valor financiado" },
    { name: "expenses_value", label: "Valor das despesas" },
    { name: "subsidy_value", label: "Valor do subsídio" },
    { name: "intended_installment_value", label: "Prestação pretendida" },
    { name: "declared_income", label: "Renda informada" },
];

const yesNoFields: Array<{ name: string; label: string }> = [
    { name: "has_other_property", label: "Possui outro imóvel?" },
    { name: "is_first_financing", label: "Primeiro financiamento?" },
    { name: "uses_fgts", label: "Utiliza FGTS?" },
    { name: "finance_documentation_fee", label: "Financiar taxa de documentação?" },
    { name: "declares_income_tax", label: "Declara IR?" },
];

const noteFields: Array<{ name: string; label: string }> = [
    { name: "contract_notes", label: "Observações do contrato" },
    { name: "purchase_value_notes", label: "Observações do valor de compra" },
    { name: "down_payment_notes", label: "Observações da entrada" },
    { name: "fgts_notes", label: "Observações do FGTS" },
    { name: "documentation_fee_notes", label: "Observações da taxa de documentação" },
    { name: "documentation_financing_notes", label: "Observações do financiamento da documentação" },
    { name: "income_tax_notes", label: "Observações do IR" },
    { name: "general_notes", label: "Observações gerais" },
];

function togglePartner(id: number, checked: boolean | "indeterminate") {
    const ids: number[] = props.form.partner_ids;
    props.form.partner_ids = checked === true ? [...ids, id] : ids.filter((partnerId) => partnerId !== id);
}

function onSubmit() {
    emit("submit");
}
</script>

<template>
    <form
        @submit.prevent="onSubmit"
        class="space-y-8 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8"
    >
        <fieldset :disabled="readonly" class="space-y-8">
            <div v-if="statuses.length">
                <h3 class="mb-6 text-lg font-semibold text-card-foreground">Status</h3>
                <StatusFields :form="form" :statuses="statuses" :finished-at="finishedAt" :disabled="readonly" />
            </div>

            <div>
                <h3 class="mb-6 text-lg font-semibold text-card-foreground">Dados da Proposta</h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <Field>
                        <FieldLabel for="creator_id">Criador *</FieldLabel>
                        <Select
                            :model-value="form.creator_id ? String(form.creator_id) : ''"
                            @update:model-value="form.creator_id = Number($event)"
                        >
                            <SelectTrigger id="creator_id">
                                <SelectValue placeholder="Selecione..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem v-for="user in options.staff" :key="user.id" :value="String(user.id)">{{
                                        user.name
                                    }}</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FieldError v-if="form.errors.creator_id">{{ form.errors.creator_id }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="analyst_id">Analista</FieldLabel>
                        <Select
                            :model-value="form.analyst_id ? String(form.analyst_id) : 'none'"
                            @update:model-value="form.analyst_id = $event === 'none' ? null : Number($event)"
                        >
                            <SelectTrigger id="analyst_id">
                                <SelectValue placeholder="Não definido" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="none">Não definido</SelectItem>
                                    <SelectItem v-for="user in options.staff" :key="user.id" :value="String(user.id)">{{
                                        user.name
                                    }}</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </Field>

                    <Field>
                        <FieldLabel for="bank_id">Banco *</FieldLabel>
                        <Select
                            :model-value="form.bank_id ? String(form.bank_id) : ''"
                            @update:model-value="form.bank_id = Number($event)"
                        >
                            <SelectTrigger id="bank_id">
                                <SelectValue placeholder="Selecione o banco..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem v-for="bank in options.banks" :key="bank.id" :value="String(bank.id)">{{
                                        bank.name
                                    }}</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FieldError v-if="form.errors.bank_id">{{ form.errors.bank_id }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="contract_type_id">Tipo de contrato *</FieldLabel>
                        <Select
                            :model-value="form.contract_type_id ? String(form.contract_type_id) : ''"
                            @update:model-value="form.contract_type_id = Number($event)"
                        >
                            <SelectTrigger id="contract_type_id">
                                <SelectValue placeholder="Selecione o contrato..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem
                                        v-for="type in options.contractTypes"
                                        :key="type.id"
                                        :value="String(type.id)"
                                        >{{ type.name }}</SelectItem
                                    >
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FieldError v-if="form.errors.contract_type_id">{{ form.errors.contract_type_id }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="amortization_table">Tabela{{ requiresFinancing ? " *" : "" }}</FieldLabel>
                        <Select
                            :model-value="form.amortization_table ? String(form.amortization_table) : ''"
                            @update:model-value="form.amortization_table = String($event)"
                        >
                            <SelectTrigger id="amortization_table">
                                <SelectValue placeholder="Selecione..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem
                                        v-for="table in options.amortizationTables"
                                        :key="table.value"
                                        :value="table.value"
                                        >{{ table.label }}</SelectItem
                                    >
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FieldError v-if="form.errors.amortization_table">{{ form.errors.amortization_table }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="payment_term">Prazo (meses){{ requiresFinancing ? " *" : "" }}</FieldLabel>
                        <Input id="payment_term" v-model.number="form.payment_term" type="number" min="1" max="600" />
                        <FieldError v-if="form.errors.payment_term">{{ form.errors.payment_term }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="property_condition">Imóvel *</FieldLabel>
                        <Select
                            :model-value="String(form.property_condition)"
                            @update:model-value="form.property_condition = String($event)"
                        >
                            <SelectTrigger id="property_condition">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem
                                        v-for="condition in options.propertyConditions"
                                        :key="condition.value"
                                        :value="condition.value"
                                        >{{ condition.label }}</SelectItem
                                    >
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FieldError v-if="form.errors.property_condition">{{ form.errors.property_condition }}</FieldError>
                    </Field>

                    <Field v-for="field in moneyFields" :key="field.name">
                        <FieldLabel :for="field.name">{{ field.label }}{{ field.required ? " *" : "" }}</FieldLabel>
                        <MoneyInput :id="field.name" v-model="form[field.name]" :nullable="!field.required" :disabled="readonly" />
                        <FieldError v-if="form.errors[field.name]">{{ form.errors[field.name] }}</FieldError>
                    </Field>

                    <Field v-for="field in yesNoFields" :key="field.name">
                        <FieldLabel :for="field.name">{{ field.label }}</FieldLabel>
                        <Select
                            :model-value="form[field.name] ? '1' : '0'"
                            @update:model-value="form[field.name] = $event === '1'"
                        >
                            <SelectTrigger :id="field.name">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="1">Sim</SelectItem>
                                    <SelectItem value="0">Não</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </Field>

                    <Field v-if="form.uses_fgts">
                        <FieldLabel for="fgts_value">Valor do FGTS</FieldLabel>
                        <MoneyInput id="fgts_value" v-model="form.fgts_value" nullable :disabled="readonly" />
                        <FieldError v-if="form.errors.fgts_value">{{ form.errors.fgts_value }}</FieldError>
                    </Field>

                    <Field v-if="form.finance_documentation_fee">
                        <FieldLabel for="documentation_fee_to_finance">Taxa de documentação a financiar</FieldLabel>
                        <MoneyInput id="documentation_fee_to_finance" v-model="form.documentation_fee_to_finance" nullable :disabled="readonly" />
                        <FieldError v-if="form.errors.documentation_fee_to_finance">{{
                            form.errors.documentation_fee_to_finance
                        }}</FieldError>
                    </Field>
                </div>
            </div>

            <div v-if="options.partners.length">
                <h3 class="mb-4 text-lg font-semibold text-card-foreground">Parceiros</h3>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div v-for="partner in options.partners" :key="partner.id" class="flex items-center gap-2">
                        <Checkbox
                            :id="`partner-${partner.id}`"
                            :model-value="form.partner_ids.includes(partner.id)"
                            @update:model-value="togglePartner(partner.id, $event)"
                        />
                        <Label :for="`partner-${partner.id}`" class="cursor-pointer">{{ partner.name }}</Label>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="mb-6 text-lg font-semibold text-card-foreground">Imóvel</h3>
                <PropertyFields
                    :property="form.property"
                    :property-types="options.propertyTypes"
                    :developments="options.developments"
                    :errors="form.errors"
                    :disabled="readonly"
                />
            </div>

            <template v-if="withPeople">
                <div>
                    <h3 class="mb-6 text-lg font-semibold text-card-foreground">Proponentes</h3>
                    <ApplicantList
                        :form="form"
                        :marital-statuses="options.maritalStatuses"
                        :bank-account-types="options.bankAccountTypes"
                    />
                </div>

                <div>
                    <h3 class="mb-6 text-lg font-semibold text-card-foreground">Vendedores</h3>
                    <SellerList
                        :form="form"
                        :person-types="options.personTypes"
                        :marital-statuses="options.maritalStatuses"
                        :bank-account-types="options.bankAccountTypes"
                    />
                </div>
            </template>

            <div>
                <h3 class="mb-6 text-lg font-semibold text-card-foreground">Observações</h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <Field v-for="field in noteFields" :key="field.name">
                        <FieldLabel :for="field.name">{{ field.label }}</FieldLabel>
                        <Textarea :id="field.name" v-model="form[field.name]" />
                    </Field>
                </div>
            </div>
        </fieldset>

        <div v-if="!readonly" class="flex justify-end border-t border-border pt-6">
            <Button
                type="submit"
                class="text-md w-full px-10 font-bold md:w-auto"
                :loading="form.processing"
                :disabled="form.processing"
            >
                {{ submitText }}
            </Button>
        </div>
    </form>
</template>
