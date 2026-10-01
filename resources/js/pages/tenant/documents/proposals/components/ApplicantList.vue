<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
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
import BankAccountFields from "./BankAccountFields.vue";
import { handleMask, maskCPF, maskPhone } from "@/lib/masks";
import { Loader2, Plus, Search, Trash } from "lucide-vue-next";
import { computed, ref } from "vue";
import axios from "axios";
import { route } from "ziggy-js";
import { useForm } from "@inertiajs/vue3";
import type { Option } from "@/types";

/**
 * Lista de proponentes montada no formulário e enviada junto com a proposta.
 * A busca pelo CPF reaproveita o proponente já cadastrado (só o CPF é enviado).
 */
const props = defineProps<{
    form: ReturnType<typeof useForm>;
    maritalStatuses: Option<number>[];
    bankAccountTypes: Option<number>[];
}>();

const loading = ref<number | null>(null);
const lookupMessage = ref<Record<number, string>>({});

const emptyApplicant = () => ({
    existing: false,
    cpf: "",
    name: "",
    email: "",
    phone: "",
    birth_date: "",
    marital_status: null as number | null,
    profession: "",
    family_income: null as number | null,
    declared_income: null as number | null,
    declares_income_tax: false,
    income_tax_notes: "",
    by_power_of_attorney: false,
    bank_account: { bank_name: "", account_type: null, branch: "", number: "" },
});

function add() {
    props.form.applicants.push(emptyApplicant());
}

function remove(index: number) {
    props.form.applicants.splice(index, 1);
}

async function lookup(index: number) {
    const applicant = props.form.applicants[index];
    loading.value = index;
    lookupMessage.value[index] = "";

    try {
        const { data } = await axios.get(route("tenant.documents.applicants.lookup"), {
            params: { cpf: applicant.cpf },
        });

        if (!data.applicant) {
            applicant.existing = false;
            lookupMessage.value[index] = "CPF não cadastrado: preencha os dados do novo proponente.";
            return;
        }

        Object.assign(applicant, {
            ...data.applicant,
            cpf: maskCPF(data.applicant.cpf),
            phone: data.applicant.phone ? maskPhone(data.applicant.phone) : "",
            bank_account: data.applicant.bank_account ?? applicant.bank_account,
        });

        lookupMessage.value[index] = data.applicant.existing
            ? "Proponente já cadastrado: os dados dele serão reaproveitados."
            : "Contato encontrado no cadastro: complete os dados do proponente.";
    } catch (error: any) {
        lookupMessage.value[index] = error?.response?.data?.message ?? "Não foi possível buscar o CPF.";
    } finally {
        loading.value = null;
    }
}

const applicants = computed(() => props.form.applicants as Record<string, any>[]);

const error = (index: number, field: string) => props.form.errors[`applicants.${index}.${field}`];
</script>

<template>
    <div class="space-y-6">
        <FieldError v-if="form.errors.applicants">{{ form.errors.applicants }}</FieldError>

        <div
            v-for="(applicant, index) in applicants"
            :key="index"
            class="space-y-4 rounded-md border border-border p-4"
        >
            <div class="flex items-center justify-between">
                <h4 class="font-semibold text-card-foreground">Proponente {{ index + 1 }}</h4>
                <Button type="button" variant="ghost" size="sm" class="text-red-600" @click="remove(index)">
                    <Trash class="mr-1 h-4 w-4" /> Remover
                </Button>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Field>
                    <FieldLabel :for="`applicant-${index}-cpf`">CPF *</FieldLabel>
                    <div class="flex gap-2">
                        <Input
                            :id="`applicant-${index}-cpf`"
                            :model-value="applicant.cpf"
                            @input="(e: Event) => handleMask(e, maskCPF, (val) => (applicant.cpf = val))"
                            placeholder="000.000.000-00"
                            :disabled="applicant.existing"
                        />
                        <Button type="button" variant="outline" :disabled="loading === index || applicant.existing" @click="lookup(index)">
                            <Loader2 v-if="loading === index" class="h-4 w-4 animate-spin" />
                            <Search v-else class="h-4 w-4" />
                        </Button>
                    </div>
                    <FieldDescription v-if="lookupMessage[index]">{{ lookupMessage[index] }}</FieldDescription>
                    <FieldError v-if="error(index, 'cpf')">{{ error(index, "cpf") }}</FieldError>
                </Field>

                <Field class="md:col-span-2">
                    <FieldLabel :for="`applicant-${index}-name`">Nome *</FieldLabel>
                    <Input :id="`applicant-${index}-name`" v-model="applicant.name" :disabled="applicant.existing" />
                    <FieldError v-if="error(index, 'name')">{{ error(index, "name") }}</FieldError>
                </Field>
            </div>

            <template v-if="!applicant.existing">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Field>
                        <FieldLabel :for="`applicant-${index}-email`">E-mail *</FieldLabel>
                        <Input :id="`applicant-${index}-email`" v-model="applicant.email" type="email" />
                        <FieldError v-if="error(index, 'email')">{{ error(index, "email") }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel :for="`applicant-${index}-phone`">Telefone *</FieldLabel>
                        <Input
                            :id="`applicant-${index}-phone`"
                            :model-value="applicant.phone"
                            @input="(e: Event) => handleMask(e, maskPhone, (val) => (applicant.phone = val))"
                        />
                        <FieldError v-if="error(index, 'phone')">{{ error(index, "phone") }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel :for="`applicant-${index}-birth_date`">Data de nascimento</FieldLabel>
                        <Input :id="`applicant-${index}-birth_date`" v-model="applicant.birth_date" type="date" />
                        <FieldError v-if="error(index, 'birth_date')">{{ error(index, "birth_date") }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel :for="`applicant-${index}-marital_status`">Estado civil *</FieldLabel>
                        <Select
                            :model-value="applicant.marital_status ? String(applicant.marital_status) : ''"
                            @update:model-value="applicant.marital_status = Number($event)"
                        >
                            <SelectTrigger :id="`applicant-${index}-marital_status`">
                                <SelectValue placeholder="Selecione..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem
                                        v-for="status in maritalStatuses"
                                        :key="status.value"
                                        :value="String(status.value)"
                                        >{{ status.label }}</SelectItem
                                    >
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FieldError v-if="error(index, 'marital_status')">{{ error(index, "marital_status") }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel :for="`applicant-${index}-profession`">Profissão *</FieldLabel>
                        <Input :id="`applicant-${index}-profession`" v-model="applicant.profession" />
                        <FieldError v-if="error(index, 'profession')">{{ error(index, "profession") }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel :for="`applicant-${index}-declared_income`">Renda declarada *</FieldLabel>
                        <MoneyInput :id="`applicant-${index}-declared_income`" v-model="applicant.declared_income" />
                        <FieldError v-if="error(index, 'declared_income')">{{ error(index, "declared_income") }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel :for="`applicant-${index}-family_income`">Renda familiar</FieldLabel>
                        <MoneyInput :id="`applicant-${index}-family_income`" v-model="applicant.family_income" nullable />
                    </Field>
                    <Field>
                        <FieldLabel :for="`applicant-${index}-declares_income_tax`">Declara IR?</FieldLabel>
                        <Select
                            :model-value="applicant.declares_income_tax ? '1' : '0'"
                            @update:model-value="applicant.declares_income_tax = $event === '1'"
                        >
                            <SelectTrigger :id="`applicant-${index}-declares_income_tax`">
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
                    <Field>
                        <FieldLabel :for="`applicant-${index}-by_power_of_attorney`">Por procuração?</FieldLabel>
                        <Select
                            :model-value="applicant.by_power_of_attorney ? '1' : '0'"
                            @update:model-value="applicant.by_power_of_attorney = $event === '1'"
                        >
                            <SelectTrigger :id="`applicant-${index}-by_power_of_attorney`">
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
                </div>

                <div>
                    <p class="mb-2 text-sm font-medium text-muted-foreground">Conta bancária (opcional)</p>
                    <BankAccountFields
                        :account="applicant.bank_account"
                        :account-types="bankAccountTypes"
                        :errors="form.errors"
                        :error-prefix="`applicants.${index}.bank_account`"
                        :id-prefix="`applicant-${index}`"
                    />
                </div>
            </template>
        </div>

        <Button type="button" variant="outline" @click="add">
            <Plus class="mr-1 h-4 w-4" /> Adicionar proponente
        </Button>
    </div>
</template>
