<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
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
import { useForm } from "@inertiajs/vue3";
import type { Option } from "@/types";

const props = withDefaults(
    defineProps<{
        form: ReturnType<typeof useForm>;
        submitText?: string;
        receiptTypes?: Option[];
    }>(),
    {
        submitText: "Salvar Tipo de Custo",
        receiptTypes: () => [],
    },
);

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
                Dados do Tipo de Custo
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="name">Nome *</FieldLabel>
                    <Input id="name" v-model="form.name" placeholder="Ex: Registro" required />
                    <FieldError v-if="form.errors.name">{{
                        form.errors.name
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="requires_notary">Vincular este custo a cartório? *</FieldLabel>
                    <Select
                        :model-value="form.requires_notary ? '1' : '0'"
                        @update:model-value="form.requires_notary = $event === '1'"
                    >
                        <SelectTrigger id="requires_notary">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.requires_notary">{{
                        form.errors.requires_notary
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="receipt_type">Gera recibo de</FieldLabel>
                    <Select
                        :model-value="form.receipt_type ? String(form.receipt_type) : 'none'"
                        @update:model-value="form.receipt_type = $event === 'none' ? '' : String($event)"
                    >
                        <SelectTrigger id="receipt_type">
                            <SelectValue placeholder="Nenhum" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="none">Nenhum</SelectItem>
                                <SelectItem
                                    v-for="option in receiptTypes"
                                    :key="option.value"
                                    :value="option.value"
                                    >{{ option.label }}</SelectItem
                                >
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.receipt_type">{{
                        form.errors.receipt_type
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
