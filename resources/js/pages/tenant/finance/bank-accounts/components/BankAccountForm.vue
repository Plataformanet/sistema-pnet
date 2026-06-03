<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
import { maskCurrency } from "@/lib/masks";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

import { useForm } from "@inertiajs/vue3";

const props = withDefaults(defineProps<{
    form: ReturnType<typeof useForm>;
    submitText?: string;
}>(), {
    submitText: "Salvar Conta Bancária",
});

const emit = defineEmits(["submit"]);

function onSubmit() {
    emit("submit");
}
</script>

<template>
    <form
        @submit.prevent="onSubmit"
        class="space-y-8 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8"
    >
        <div class="mb-8">
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">
                Dados da Conta Bancária
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="name">Nome da Conta *</FieldLabel>
                    <Input id="name" v-model="form.name" placeholder="Ex: Conta Principal Itaú, Caixinha da Empresa" required />
                    <FieldError v-if="form.errors.name">{{
                        form.errors.name
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="bank">Banco *</FieldLabel>
                    <Input id="bank" v-model="form.bank" placeholder="Ex: Itaú, Bradesco, Santander, Caixa" required />
                    <FieldError v-if="form.errors.bank">{{
                        form.errors.bank
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="agency">Agência *</FieldLabel>
                    <Input id="agency" v-model="form.agency" placeholder="Ex: 0001, 1234" required />
                    <FieldError v-if="form.errors.agency">{{
                        form.errors.agency
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="account_number">Número da Conta *</FieldLabel>
                    <Input id="account_number" v-model="form.account_number" placeholder="Ex: 12345-6" required />
                    <FieldError v-if="form.errors.account_number">{{
                        form.errors.account_number
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="account_type">Tipo de Conta *</FieldLabel>
                    <Select
                        :model-value="form.account_type ? String(form.account_type) : ''"
                        @update:model-value="form.account_type = $event"
                    >
                        <SelectTrigger id="account_type">
                            <SelectValue placeholder="Selecione o tipo..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="Conta Corrente">Conta Corrente</SelectItem>
                                <SelectItem value="Conta Poupança">Conta Poupança</SelectItem>
                                <SelectItem value="Outros">Outros</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.account_type">{{
                        form.errors.account_type
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="initial_balance">Saldo Inicial *</FieldLabel>
                    <Input
                        id="initial_balance"
                        :model-value="form.initial_balance"
                        @input="
                            (e: Event) => {
                                const val = maskCurrency(
                                    (e.target as HTMLInputElement).value,
                                );
                                form.initial_balance = val;
                                (e.target as HTMLInputElement).value = val;
                            }
                        "
                        placeholder="R$ 0,00"
                        required
                    />
                    <FieldError v-if="form.errors.initial_balance">{{
                        form.errors.initial_balance
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="main_account">Conta Principal?</FieldLabel>
                    <Select
                        :model-value="form.main_account ? '1' : '0'"
                        @update:model-value="form.main_account = $event === '1'"
                    >
                        <SelectTrigger id="main_account">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.main_account">{{
                        form.errors.main_account
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="active">Status</FieldLabel>
                    <Select
                        :model-value="form.active ? '1' : '0'"
                        @update:model-value="form.active = $event === '1'"
                    >
                        <SelectTrigger id="active">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Ativa</SelectItem>
                                <SelectItem value="0">Inativa</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.active">{{
                        form.errors.active
                    }}</FieldError>
                </Field>
            </div>
        </div>

        <div class="flex justify-end border-t border-border pt-6">
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
