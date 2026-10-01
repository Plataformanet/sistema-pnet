<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
import { Textarea } from "@/components/ui/textarea";
import { handleMask, maskCurrency } from "@/lib/masks";
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
        submitText: "Salvar Serviço",
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
                Dados do Serviço
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="name">Serviço *</FieldLabel>
                    <Input id="name" v-model="form.name" placeholder="Ex: Assessoria documental" required />
                    <FieldError v-if="form.errors.name">{{
                        form.errors.name
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-2">
                    <FieldLabel for="description">Pequena descrição *</FieldLabel>
                    <Textarea id="description" v-model="form.description" required />
                    <FieldError v-if="form.errors.description">{{
                        form.errors.description
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="price">Valor *</FieldLabel>
                    <Input
                        id="price"
                        :model-value="form.price"
                        @input="(e: Event) => handleMask(e, maskCurrency, (val) => (form.price = val))" placeholder="R$ 0,00" required
                    />
                    <FieldError v-if="form.errors.price">{{
                        form.errors.price
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="generates_receipt">Gerar recibo? *</FieldLabel>
                    <Select
                        :model-value="form.generates_receipt ? '1' : '0'"
                        @update:model-value="form.generates_receipt = $event === '1'"
                    >
                        <SelectTrigger id="generates_receipt">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.generates_receipt">{{
                        form.errors.generates_receipt
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
