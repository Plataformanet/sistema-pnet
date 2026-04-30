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
    submitText: "Salvar Produto",
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
        <!-- Dados Principais -->
        <div class="mb-8">
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">
                Dados Principais
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="name">Nome do Produto *</FieldLabel>
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
                    <FieldLabel for="barcode"
                        >Código de Barras (EAN/GTIN)</FieldLabel
                    >
                    <Input id="barcode" v-model="form.barcode" />
                    <FieldError v-if="form.errors.barcode">{{
                        form.errors.barcode
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="category_id"
                        >Categoria do Produto</FieldLabel
                    >
                    <Select v-model="form.category_id">
                        <SelectTrigger id="category_id">
                            <SelectValue
                                placeholder="Selecione uma categoria"
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1"
                                    >Informática / Computadores</SelectItem
                                >
                                <SelectItem value="2">Periféricos</SelectItem>
                                <SelectItem value="3"
                                    >Suprimentos e Papelaria</SelectItem
                                >
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.category_id">{{
                        form.errors.category_id
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="unit_of_measure"
                        >Unidade de Medida</FieldLabel
                    >
                    <Select v-model="form.unit_of_measure">
                        <SelectTrigger id="unit_of_measure">
                            <SelectValue placeholder="Selecione a UN" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="un">Unidade (Un)</SelectItem>
                                <SelectItem value="cx">Caixa (Cx)</SelectItem>
                                <SelectItem value="kg"
                                    >Quilograma (Kg)</SelectItem
                                >
                                <SelectItem value="l">Litro (l)</SelectItem>
                                <SelectItem value="m">Metro (m)</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.unit_of_measure">{{
                        form.errors.unit_of_measure
                    }}</FieldError>
                </Field>
            </div>

            <!-- Valores -->
            <h3 class="mt-8 mb-6 text-lg font-semibold text-card-foreground">
                Valores
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
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
            </div>

            <!-- Logística e Estoque -->
            <h3 class="mt-8 mb-6 text-lg font-semibold text-card-foreground">
                Estoque e Controle
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <Field>
                    <FieldLabel for="manage_stock"
                        >Controla Estoque?</FieldLabel
                    >
                    <Select
                        :model-value="form.manage_stock ? '1' : '0'"
                        @update:model-value="form.manage_stock = $event === '1'"
                    >
                        <SelectTrigger id="manage_stock">
                            <SelectValue placeholder="Controle" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.manage_stock">{{
                        form.errors.manage_stock
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="current_stock">Estoque Atual</FieldLabel>
                    <Input
                        id="current_stock"
                        type="number"
                        v-model="form.current_stock"
                        :disabled="!form.manage_stock"
                    />
                    <FieldError v-if="form.errors.current_stock">{{
                        form.errors.current_stock
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="min_stock">Estoque Mínimo</FieldLabel>
                    <Input
                        id="min_stock"
                        type="number"
                        v-model="form.min_stock"
                        :disabled="!form.manage_stock"
                    />
                    <FieldError v-if="form.errors.min_stock">{{
                        form.errors.min_stock
                    }}</FieldError>
                </Field>
            </div>

            <!-- Detalhes e Status -->
            <h3 class="mt-8 mb-6 text-lg font-semibold text-card-foreground">
                Informações Adicionais
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="description">Descrição</FieldLabel>
                    <textarea
                        id="description"
                        v-model="form.description"
                        class="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Detalhes ou especificações técnicas do produto..."
                    ></textarea>
                    <FieldError v-if="form.errors.description">{{
                        form.errors.description
                    }}</FieldError>
                </Field>

                <Field class="md:w-1/2">
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
