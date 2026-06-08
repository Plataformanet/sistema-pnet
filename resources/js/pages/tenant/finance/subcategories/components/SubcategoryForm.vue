<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
import { Textarea } from "@/components/ui/textarea";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

import { useForm } from "@inertiajs/vue3";
import { FinanceCategory } from "@/types";

const props = withDefaults(defineProps<{
    form: ReturnType<typeof useForm>;
    categories: FinanceCategory[];
    submitText?: string;
}>(), {
    submitText: "Salvar Subcategoria",
});

const emit = defineEmits(["submit"]);

function onSubmit() {
    emit("submit");
}

function getCategoryLabel(category: FinanceCategory) {
    return `${category.name} (${category.type === 'despesa' ? 'Saída / Despesa' : 'Entrada / Receita'})`;
}
</script>

<template>
    <form
        @submit.prevent="onSubmit"
        class="space-y-8 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8"
    >
        <div class="mb-8">
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">
                Dados da Subcategoria Financeira
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="financial_category_id">Categoria Financeira *</FieldLabel>
                    <Select
                        :model-value="form.financial_category_id ? String(form.financial_category_id) : ''"
                        @update:model-value="form.financial_category_id = Number($event)"
                    >
                        <SelectTrigger id="financial_category_id">
                            <SelectValue placeholder="Selecione uma categoria..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="String(category.id)"
                                >
                                    {{ getCategoryLabel(category) }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.financial_category_id">{{
                        form.errors.financial_category_id
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-1">
                    <FieldLabel for="name">Nome da Subcategoria *</FieldLabel>
                    <Input id="name" v-model="form.name" required />
                    <FieldError v-if="form.errors.name">{{
                        form.errors.name
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-1">
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

                <Field class="md:col-span-2">
                    <FieldLabel for="observations">Observações</FieldLabel>
                    <Textarea
                        id="observations"
                        v-model="form.observations"
                        placeholder="Observações adicionais sobre esta subcategoria..."
                    />
                    <FieldError v-if="form.errors.observations">{{
                        form.errors.observations
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
