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
    submitText: "Salvar Serviço",
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
                Dados do Serviço
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="name">Nome do Serviço *</FieldLabel>
                    <Input id="name" v-model="form.name" required />
                    <FieldError v-if="form.errors.name">{{
                        form.errors.name
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="sku">Código/SKU</FieldLabel>
                    <Input id="sku" v-model="form.sku" />
                    <FieldError v-if="form.errors.sku">{{
                        form.errors.sku
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="category_id"
                        >Categoria de Serviço</FieldLabel
                    >
                    <Select v-model="form.category_id">
                        <SelectTrigger id="category_id">
                            <SelectValue
                                placeholder="Selecione uma categoria"
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Consultoria</SelectItem>
                                <SelectItem value="2"
                                    >Desenvolvimento</SelectItem
                                >
                                <SelectItem value="3"
                                    >Suporte e Manutenção</SelectItem
                                >
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.category_id">{{
                        form.errors.category_id
                    }}</FieldError>
                </Field>
            </div>

            <h3 class="mt-8 mb-6 text-lg font-semibold text-card-foreground">
                Valores e Detalhes
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <Field>
                    <FieldLabel for="cost_value">Valor de Custo</FieldLabel>
                    <Input
                        id="cost_value"
                        :model-value="form.cost_value"
                        @input="
                            (e: Event) => {
                                const val = maskCurrency(
                                    (e.target as HTMLInputElement).value,
                                );
                                form.cost_value = val;
                                (e.target as HTMLInputElement).value = val;
                            }
                        "
                        placeholder="R$ 0,00"
                    />
                    <FieldError v-if="form.errors.cost_value">{{
                        form.errors.cost_value
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="sell_value">Valor de Venda</FieldLabel>
                    <Input
                        id="sell_value"
                        :model-value="form.sell_value"
                        @input="
                            (e: Event) => {
                                const val = maskCurrency(
                                    (e.target as HTMLInputElement).value,
                                );
                                form.sell_value = val;
                                (e.target as HTMLInputElement).value = val;
                            }
                        "
                        placeholder="R$ 0,00"
                    />
                    <FieldError v-if="form.errors.sell_value">{{
                        form.errors.sell_value
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="fees">Emolumentos</FieldLabel>
                    <Input
                        id="fees"
                        :model-value="form.fees"
                        @input="
                            (e: Event) => {
                                const val = maskCurrency(
                                    (e.target as HTMLInputElement).value,
                                );
                                form.fees = val;
                                (e.target as HTMLInputElement).value = val;
                            }
                        "
                        placeholder="R$ 0,00"
                    />
                    <FieldError v-if="form.errors.fees">{{
                        form.errors.fees
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-3">
                    <FieldLabel for="description"
                        >Descrição do Serviço</FieldLabel
                    >
                    <textarea
                        id="description"
                        v-model="form.description"
                        class="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Detalhes sobre o serviço prestado..."
                    ></textarea>
                    <FieldError v-if="form.errors.description">{{
                        form.errors.description
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="duration">Duração (minutos)</FieldLabel>
                    <Input
                        id="duration"
                        type="number"
                        v-model="form.duration"
                        placeholder="Ex: 60"
                    />
                    <FieldError v-if="form.errors.duration">{{
                        form.errors.duration
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
                                <SelectItem value="1">Ativo</SelectItem>
                                <SelectItem value="0">Inativo</SelectItem>
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
                :disabled="form.processing"
            >
                {{ submitText }}
            </Button>
        </div>
    </form>
</template>
