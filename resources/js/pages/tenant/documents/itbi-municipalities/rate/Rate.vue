<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import FieldError from "@/components/ui/field/FieldError.vue";
import MoneyInput from "@/components/MoneyInput.vue";
import { trimRate } from "@/lib/masks";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import type { ItbiMunicipality } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    municipality: ItbiMunicipality;
    moduleLabel: string;
    fields: string[];
}>();

const rate = props.municipality.rate ?? {};

const form = useForm({
    own_funds_rate: trimRate(rate.own_funds_rate),
    financed_rate: trimRate(rate.financed_rate),
    financed_cap_amount: (rate.financed_cap_amount as number | null) ?? null,
    first_property_financed_rate: trimRate(rate.first_property_financed_rate),
    other_property_financed_rate: trimRate(rate.other_property_financed_rate),
});

const percentFields: Array<{ name: "financed_rate" | "first_property_financed_rate" | "other_property_financed_rate"; label: string }> = [
    { name: "financed_rate", label: "Alíquota sobre o valor financiado (%)" },
    { name: "first_property_financed_rate", label: "Alíquota do financiado — primeiro imóvel (%)" },
    { name: "other_property_financed_rate", label: "Alíquota do financiado — demais imóveis (%)" },
];

function submit() {
    form.put(route("tenant.documents.itbi-municipalities.rates.update", props.municipality.id));
}
</script>

<template>
    <Head :title="`Alíquotas de ITBI — ${municipality.name}`" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">Alíquotas de ITBI — {{ municipality.name }}/{{ municipality.state }}</h2>
            <p class="text-sm text-muted-foreground">{{ moduleLabel }}</p>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.itbi-municipalities.list')"><ChevronLeft class="mr-2 h-4 w-4" /> Voltar</Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <form @submit.prevent="submit" class="space-y-8 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field>
                    <FieldLabel for="own_funds_rate">Alíquota sobre recursos próprios (%) *</FieldLabel>
                    <Input id="own_funds_rate" v-model="form.own_funds_rate" type="number" step="0.0001" min="0" max="100" required />
                    <FieldDescription>Também usada como alíquota cheia no registro em geral e em SFI.</FieldDescription>
                    <FieldError v-if="form.errors.own_funds_rate">{{ form.errors.own_funds_rate }}</FieldError>
                </Field>

                <template v-for="field in percentFields" :key="field.name">
                    <Field v-if="fields.includes(field.name)">
                        <FieldLabel :for="field.name">{{ field.label }} *</FieldLabel>
                        <Input :id="field.name" v-model="form[field.name]" type="number" step="0.0001" min="0" max="100" required />
                        <FieldError v-if="form.errors[field.name]">{{ form.errors[field.name] }}</FieldError>
                    </Field>
                </template>

                <Field v-if="fields.includes('financed_cap_amount')">
                    <FieldLabel for="financed_cap_amount">Teto do valor financiado *</FieldLabel>
                    <MoneyInput id="financed_cap_amount" v-model="form.financed_cap_amount" nullable />
                    <FieldDescription>Acima do teto, o valor financiado paga a alíquota de recursos próprios.</FieldDescription>
                    <FieldError v-if="form.errors.financed_cap_amount">{{ form.errors.financed_cap_amount }}</FieldError>
                </Field>
            </div>

            <div class="flex justify-end border-t border-border pt-6">
                <Button type="submit" class="text-md w-full px-10 font-bold md:w-auto" :disabled="form.processing">Salvar Alíquotas</Button>
            </div>
        </form>
    </div>
</template>
