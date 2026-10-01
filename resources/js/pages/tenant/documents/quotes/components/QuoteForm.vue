<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import ServicePicker from "@/pages/tenant/documents/fee-calculator/components/ServicePicker.vue";
import { handleMask, maskCPF, maskPhone } from "@/lib/masks";
import { useForm } from "@inertiajs/vue3";
import type { BillableServiceOption, Option } from "@/types";

const props = withDefaults(
    defineProps<{
        form: ReturnType<typeof useForm>;
        banks: Array<{ id: number; name: string }>;
        services: BillableServiceOption[];
        maritalStatuses: Option<number>[];
        submitText?: string;
    }>(),
    { submitText: "Salvar Orçamento" },
);

const emit = defineEmits(["submit"]);

const today = new Date().toISOString().slice(0, 10);
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-8 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8">
        <div>
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">Dados do Cliente</h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <Field class="md:col-span-2">
                    <FieldLabel for="quote_name">Nome *</FieldLabel>
                    <Input id="quote_name" v-model="form.name" required />
                    <FieldError v-if="form.errors.name">{{ form.errors.name }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="quote_cpf">CPF *</FieldLabel>
                    <Input id="quote_cpf" :model-value="form.cpf" @input="(e: Event) => handleMask(e, maskCPF, (val) => (props.form.cpf = val))" required />
                    <FieldError v-if="form.errors.cpf">{{ form.errors.cpf }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="quote_email">E-mail *</FieldLabel>
                    <Input id="quote_email" v-model="form.email" type="email" required />
                    <FieldError v-if="form.errors.email">{{ form.errors.email }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="quote_phone">Telefone *</FieldLabel>
                    <Input id="quote_phone" :model-value="form.phone" @input="(e: Event) => handleMask(e, maskPhone, (val) => (props.form.phone = val))" required />
                    <FieldError v-if="form.errors.phone">{{ form.errors.phone }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="quote_profession">Profissão *</FieldLabel>
                    <Input id="quote_profession" v-model="form.profession" required />
                    <FieldError v-if="form.errors.profession">{{ form.errors.profession }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="quote_marital_status">Estado civil *</FieldLabel>
                    <Select :model-value="form.marital_status ? String(form.marital_status) : ''" @update:model-value="form.marital_status = Number($event)">
                        <SelectTrigger id="quote_marital_status">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="status in maritalStatuses" :key="status.value" :value="String(status.value)">{{ status.label }}</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.marital_status">{{ form.errors.marital_status }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="quote_bank_id">Banco *</FieldLabel>
                    <Select :model-value="form.bank_id ? String(form.bank_id) : ''" @update:model-value="form.bank_id = Number($event)">
                        <SelectTrigger id="quote_bank_id">
                            <SelectValue placeholder="Selecione o banco..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="bank in banks" :key="bank.id" :value="String(bank.id)">{{ bank.name }}</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.bank_id">{{ form.errors.bank_id }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="quote_valid_until">Validade *</FieldLabel>
                    <Input id="quote_valid_until" v-model="form.valid_until" type="date" :min="today" required />
                    <FieldError v-if="form.errors.valid_until">{{ form.errors.valid_until }}</FieldError>
                </Field>
            </div>
        </div>

        <div>
            <h3 class="mb-4 text-lg font-semibold text-card-foreground">Serviços</h3>
            <ServicePicker v-model="form.services" :services="services" />
            <FieldError v-for="(message, key) in form.errors" v-show="String(key).startsWith('services.')" :key="key">{{ message }}</FieldError>
        </div>

        <div class="flex justify-end border-t border-border pt-6">
            <Button type="submit" class="text-md w-full px-10 font-bold md:w-auto" :loading="form.processing" :disabled="form.processing">
                {{ submitText }}
            </Button>
        </div>
    </form>
</template>
