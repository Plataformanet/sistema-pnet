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

const props = withDefaults(defineProps<{
    form: ReturnType<typeof useForm>;
    submitText?: string;
}>(), {
    submitText: "Salvar Categoria",
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
                Dados da Categoria Financeira
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="name">Nome da Categoria *</FieldLabel>
                    <Input id="name" v-model="form.name" required />
                    <FieldError v-if="form.errors.name">{{
                        form.errors.name
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-1">
                    <FieldLabel for="type">Tipo *</FieldLabel>
                    <Select
                        :model-value="form.type ?? ''"
                        @update:model-value="form.type = $event"
                    >
                        <SelectTrigger id="type">
                            <SelectValue placeholder="Selecione o tipo" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="despesa">Saída / Despesa</SelectItem>
                                <SelectItem value="receita">Entrada / Receita</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.type">{{
                        form.errors.type
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
                        placeholder="Observações adicionais sobre esta categoria..."
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
