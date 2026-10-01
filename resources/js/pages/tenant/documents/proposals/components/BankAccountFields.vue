<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import FieldError from "@/components/ui/field/FieldError.vue";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import type { HolderBankAccount, Option } from "@/types";

const props = defineProps<{
    account: Partial<HolderBankAccount>;
    accountTypes: Option<number>[];
    errors: Record<string, string>;
    errorPrefix: string;
    idPrefix: string;
    disabled?: boolean;
}>();

const error = (field: string) => props.errors[`${props.errorPrefix}.${field}`];
</script>

<template>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <Field>
            <FieldLabel :for="`${idPrefix}-bank_name`">Banco</FieldLabel>
            <Input :id="`${idPrefix}-bank_name`" v-model="account.bank_name" :disabled="disabled" />
            <FieldError v-if="error('bank_name')">{{ error("bank_name") }}</FieldError>
        </Field>
        <Field>
            <FieldLabel :for="`${idPrefix}-account_type`">Tipo de conta</FieldLabel>
            <Select
                :model-value="account.account_type !== undefined && account.account_type !== null ? String(account.account_type) : ''"
                @update:model-value="account.account_type = Number($event)"
                :disabled="disabled"
            >
                <SelectTrigger :id="`${idPrefix}-account_type`">
                    <SelectValue placeholder="Selecione..." />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem
                            v-for="type in accountTypes"
                            :key="type.value"
                            :value="String(type.value)"
                            >{{ type.label }}</SelectItem
                        >
                    </SelectGroup>
                </SelectContent>
            </Select>
            <FieldError v-if="error('account_type')">{{ error("account_type") }}</FieldError>
        </Field>
        <Field>
            <FieldLabel :for="`${idPrefix}-branch`">Agência</FieldLabel>
            <Input :id="`${idPrefix}-branch`" v-model="account.branch" :disabled="disabled" />
            <FieldError v-if="error('branch')">{{ error("branch") }}</FieldError>
        </Field>
        <Field>
            <FieldLabel :for="`${idPrefix}-number`">Conta</FieldLabel>
            <Input :id="`${idPrefix}-number`" v-model="account.number" :disabled="disabled" />
            <FieldError v-if="error('number')">{{ error("number") }}</FieldError>
        </Field>
    </div>
</template>
