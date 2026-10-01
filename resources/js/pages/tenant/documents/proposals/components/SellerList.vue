<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
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
import { handleMask, maskCNPJ, maskCPF, maskPhone } from "@/lib/masks";
import { Plus, Trash } from "lucide-vue-next";
import { computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import type { Option } from "@/types";

const props = defineProps<{
    form: ReturnType<typeof useForm>;
    personTypes: Option[];
    maritalStatuses: Option<number>[];
    bankAccountTypes: Option<number>[];
}>();

function add() {
    props.form.sellers.push({
        person_type: "PF",
        document: "",
        name: "",
        email: "",
        phone: "",
        marital_status: null,
        profession: "",
        declared_income: null,
        declares_income_tax: false,
        by_power_of_attorney: false,
        bank_account: { bank_name: "", account_type: null, branch: "", number: "" },
    });
}

function remove(index: number) {
    props.form.sellers.splice(index, 1);
}

const sellers = computed(() => props.form.sellers as Record<string, any>[]);

const error = (index: number, field: string) => props.form.errors[`sellers.${index}.${field}`];
</script>

<template>
    <div class="space-y-6">
        <p v-if="!sellers.length" class="text-sm text-muted-foreground">Nenhum vendedor adicionado.</p>

        <div v-for="(seller, index) in sellers" :key="index" class="space-y-4 rounded-md border border-border p-4">
            <div class="flex items-center justify-between">
                <h4 class="font-semibold text-card-foreground">Vendedor {{ index + 1 }}</h4>
                <Button type="button" variant="ghost" size="sm" class="text-red-600" @click="remove(index)">
                    <Trash class="mr-1 h-4 w-4" /> Remover
                </Button>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Field>
                    <FieldLabel :for="`seller-${index}-person_type`">Tipo de pessoa *</FieldLabel>
                    <Select
                        :model-value="seller.person_type"
                        @update:model-value="
                            seller.person_type = String($event);
                            seller.document = '';
                        "
                    >
                        <SelectTrigger :id="`seller-${index}-person_type`">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="type in personTypes" :key="type.value" :value="type.value">{{
                                    type.label
                                }}</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </Field>
                <Field>
                    <FieldLabel :for="`seller-${index}-document`">{{ seller.person_type === "PJ" ? "CNPJ" : "CPF" }} *</FieldLabel>
                    <Input
                        :id="`seller-${index}-document`"
                        :model-value="seller.document"
                        @input="(e: Event) => handleMask(e, seller.person_type === 'PJ' ? maskCNPJ : maskCPF, (val) => (seller.document = val))"
                    />
                    <FieldError v-if="error(index, 'document')">{{ error(index, "document") }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel :for="`seller-${index}-name`">{{ seller.person_type === "PJ" ? "Razão social" : "Nome" }} *</FieldLabel>
                    <Input :id="`seller-${index}-name`" v-model="seller.name" />
                    <FieldError v-if="error(index, 'name')">{{ error(index, "name") }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel :for="`seller-${index}-email`">E-mail *</FieldLabel>
                    <Input :id="`seller-${index}-email`" v-model="seller.email" type="email" />
                    <FieldError v-if="error(index, 'email')">{{ error(index, "email") }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel :for="`seller-${index}-phone`">Telefone</FieldLabel>
                    <Input
                        :id="`seller-${index}-phone`"
                        :model-value="seller.phone"
                        @input="(e: Event) => handleMask(e, maskPhone, (val) => (seller.phone = val))"
                    />
                </Field>
                <template v-if="seller.person_type === 'PF'">
                    <Field>
                        <FieldLabel :for="`seller-${index}-marital_status`">Estado civil</FieldLabel>
                        <Select
                            :model-value="seller.marital_status ? String(seller.marital_status) : ''"
                            @update:model-value="seller.marital_status = Number($event)"
                        >
                            <SelectTrigger :id="`seller-${index}-marital_status`">
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
                    </Field>
                    <Field>
                        <FieldLabel :for="`seller-${index}-profession`">Profissão</FieldLabel>
                        <Input :id="`seller-${index}-profession`" v-model="seller.profession" />
                    </Field>
                    <Field>
                        <FieldLabel :for="`seller-${index}-declared_income`">Renda declarada</FieldLabel>
                        <MoneyInput :id="`seller-${index}-declared_income`" v-model="seller.declared_income" nullable />
                    </Field>
                </template>
            </div>

            <div>
                <p class="mb-2 text-sm font-medium text-muted-foreground">Conta bancária para crédito (opcional)</p>
                <BankAccountFields
                    :account="seller.bank_account"
                    :account-types="bankAccountTypes"
                    :errors="form.errors"
                    :error-prefix="`sellers.${index}.bank_account`"
                    :id-prefix="`seller-${index}`"
                />
            </div>
        </div>

        <Button type="button" variant="outline" @click="add">
            <Plus class="mr-1 h-4 w-4" /> Adicionar vendedor
        </Button>
    </div>
</template>
