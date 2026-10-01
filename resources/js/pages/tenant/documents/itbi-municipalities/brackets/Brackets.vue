<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import FieldError from "@/components/ui/field/FieldError.vue";
import MoneyInput from "@/components/MoneyInput.vue";
import { ChevronLeft, Plus, Trash } from "lucide-vue-next";
import { route } from "ziggy-js";
import type { ItbiMunicipality } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    municipality: ItbiMunicipality;
}>();

interface BracketRow {
    min_value: number | null;
    max_value: number | null;
    rate: string;
    discount_amount: number | null;
}

const form = useForm({
    full_rate: props.municipality.full_rate ?? "",
    brackets: (props.municipality.brackets ?? []).map((bracket) => ({
        min_value: bracket.min_value,
        max_value: bracket.max_value,
        rate: bracket.rate,
        discount_amount: bracket.discount_amount,
    })) as BracketRow[],
});

function add() {
    const last = form.brackets[form.brackets.length - 1];
    form.brackets.push({ min_value: last?.max_value ? last.max_value + 1 : 0, max_value: null, rate: "", discount_amount: 0 });
}

function remove(index: number) {
    form.brackets.splice(index, 1);
}

const error = (index: number, field: string) => form.errors[`brackets.${index}.${field}` as keyof typeof form.errors];

function submit() {
    form.put(route("tenant.documents.itbi-municipalities.brackets.update", props.municipality.id));
}
</script>

<template>
    <Head :title="`Faixas de ITBI — ${municipality.name}`" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <h2 class="text-3xl font-bold tracking-tight text-foreground">Faixas de ITBI — {{ municipality.name }}/{{ municipality.state }}</h2>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.itbi-municipalities.list')"><ChevronLeft class="mr-2 h-4 w-4" /> Voltar</Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <form @submit.prevent="submit" class="space-y-8 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8">
            <Field class="max-w-sm">
                <FieldLabel for="full_rate">Alíquota cheia (%) *</FieldLabel>
                <Input id="full_rate" v-model="form.full_rate" type="number" step="0.0001" min="0" max="100" required />
                <FieldDescription>Usada no registro em geral e na compra com SFI.</FieldDescription>
                <FieldError v-if="form.errors.full_rate">{{ form.errors.full_rate }}</FieldError>
            </Field>

            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-card-foreground">Faixas (compra com SFH)</h3>
                <p class="text-sm text-muted-foreground">Limites inclusivos: um imóvel de valor igual ao máximo está dentro da faixa. As faixas não podem se sobrepor.</p>
                <FieldError v-if="form.errors.brackets">{{ form.errors.brackets }}</FieldError>

                <div v-for="(bracket, index) in form.brackets" :key="index" class="grid grid-cols-1 items-end gap-3 rounded-md border border-border p-3 md:grid-cols-5">
                    <Field>
                        <FieldLabel>Valor mínimo *</FieldLabel>
                        <MoneyInput v-model="bracket.min_value" nullable />
                        <FieldError v-if="error(index, 'min_value')">{{ error(index, "min_value") }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel>Valor máximo *</FieldLabel>
                        <MoneyInput v-model="bracket.max_value" nullable />
                        <FieldError v-if="error(index, 'max_value')">{{ error(index, "max_value") }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel>Alíquota (%) *</FieldLabel>
                        <Input v-model="bracket.rate" type="number" step="0.0001" min="0" max="100" />
                        <FieldError v-if="error(index, 'rate')">{{ error(index, "rate") }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel>Desconto</FieldLabel>
                        <MoneyInput v-model="bracket.discount_amount" nullable />
                    </Field>
                    <Button type="button" variant="ghost" class="text-red-600" @click="remove(index)"><Trash class="h-4 w-4" /></Button>
                </div>

                <Button type="button" variant="outline" @click="add"><Plus class="mr-1 h-4 w-4" /> Adicionar faixa</Button>
            </div>

            <div class="flex justify-end border-t border-border pt-6">
                <Button type="submit" class="text-md w-full px-10 font-bold md:w-auto" :disabled="form.processing">Salvar Faixas</Button>
            </div>
        </form>
    </div>
</template>
