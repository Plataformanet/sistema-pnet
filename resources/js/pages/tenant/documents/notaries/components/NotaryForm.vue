<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
import { Textarea } from "@/components/ui/textarea";
import { handleMask, maskCEP } from "@/lib/masks";
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
        states?: Option[];
    }>(),
    {
        submitText: "Salvar Cartório",
        states: () => [],
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
                Dados do Cartório
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="name">Nome *</FieldLabel>
                    <Input id="name" v-model="form.name" placeholder="Ex: 1º Oficial de Registro de Imóveis" required />
                    <FieldError v-if="form.errors.name">{{
                        form.errors.name
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="zip_code">CEP *</FieldLabel>
                    <Input
                        id="zip_code"
                        :model-value="form.zip_code"
                        @input="(e: Event) => handleMask(e, maskCEP, (val) => (form.zip_code = val))" placeholder="00000-000" required
                    />
                    <FieldError v-if="form.errors.zip_code">{{
                        form.errors.zip_code
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="street">Endereço *</FieldLabel>
                    <Input id="street" v-model="form.street" placeholder="Rua, avenida..." required />
                    <FieldError v-if="form.errors.street">{{
                        form.errors.street
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="number">Número *</FieldLabel>
                    <Input id="number" v-model="form.number" required />
                    <FieldError v-if="form.errors.number">{{
                        form.errors.number
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="complement">Complemento</FieldLabel>
                    <Input id="complement" v-model="form.complement" />
                    <FieldError v-if="form.errors.complement">{{
                        form.errors.complement
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="neighborhood">Bairro *</FieldLabel>
                    <Input id="neighborhood" v-model="form.neighborhood" required />
                    <FieldError v-if="form.errors.neighborhood">{{
                        form.errors.neighborhood
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="city">Cidade *</FieldLabel>
                    <Input id="city" v-model="form.city" required />
                    <FieldError v-if="form.errors.city">{{
                        form.errors.city
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="state">Estado *</FieldLabel>
                    <Select
                        :model-value="form.state ? String(form.state) : ''"
                        @update:model-value="form.state = String($event)"
                    >
                        <SelectTrigger id="state">
                            <SelectValue placeholder="Selecione o estado..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem
                                    v-for="option in states"
                                    :key="option.value"
                                    :value="option.value"
                                    >{{ option.label }}</SelectItem
                                >
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.state">{{
                        form.errors.state
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="reference_point">Ponto de referência</FieldLabel>
                    <Input id="reference_point" v-model="form.reference_point" />
                    <FieldError v-if="form.errors.reference_point">{{
                        form.errors.reference_point
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-2">
                    <FieldLabel for="business_hours">Horários de atendimento / Observação</FieldLabel>
                    <Textarea id="business_hours" v-model="form.business_hours" />
                    <FieldError v-if="form.errors.business_hours">{{
                        form.errors.business_hours
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
