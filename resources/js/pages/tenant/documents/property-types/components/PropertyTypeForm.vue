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

const props = withDefaults(
    defineProps<{
        form: ReturnType<typeof useForm>;
        submitText?: string;
    }>(),
    {
        submitText: "Salvar Tipo de Imóvel",
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
                Dados do Tipo de Imóvel
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="name">Nome *</FieldLabel>
                    <Input id="name" v-model="form.name" placeholder="Ex: Apartamento" required />
                    <FieldError v-if="form.errors.name">{{
                        form.errors.name
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="shows_number">Preencher número? *</FieldLabel>
                    <Select
                        :model-value="form.shows_number ? '1' : '0'"
                        @update:model-value="form.shows_number = $event === '1'"
                    >
                        <SelectTrigger id="shows_number">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.shows_number">{{
                        form.errors.shows_number
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="shows_complement">Preencher complemento? *</FieldLabel>
                    <Select
                        :model-value="form.shows_complement ? '1' : '0'"
                        @update:model-value="form.shows_complement = $event === '1'"
                    >
                        <SelectTrigger id="shows_complement">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.shows_complement">{{
                        form.errors.shows_complement
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="requires_development">Preencher empreendimento? *</FieldLabel>
                    <Select
                        :model-value="form.requires_development ? '1' : '0'"
                        @update:model-value="form.requires_development = $event === '1'"
                    >
                        <SelectTrigger id="requires_development">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.requires_development">{{
                        form.errors.requires_development
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="shows_unit">Preencher unidade? *</FieldLabel>
                    <Select
                        :model-value="form.shows_unit ? '1' : '0'"
                        @update:model-value="form.shows_unit = $event === '1'"
                    >
                        <SelectTrigger id="shows_unit">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.shows_unit">{{
                        form.errors.shows_unit
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="shows_block">Preencher bloco? *</FieldLabel>
                    <Select
                        :model-value="form.shows_block ? '1' : '0'"
                        @update:model-value="form.shows_block = $event === '1'"
                    >
                        <SelectTrigger id="shows_block">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.shows_block">{{
                        form.errors.shows_block
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
