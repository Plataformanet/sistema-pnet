<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { Field, FieldLabel } from "@/components/ui/field";
import FieldError from "@/components/ui/field/FieldError.vue";
import ComboboxRemote from "@/components/ui/combobox/ComboboxRemote.vue";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import FeeResultTable from "../components/FeeResultTable.vue";
import ServicePicker from "../components/ServicePicker.vue";
import type { BillableServiceOption, FeeCalculationSummary, ServiceSelection } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    calculation: FeeCalculationSummary;
    services: BillableServiceOption[];
}>();

const form = useForm({
    proposal_id: null as number | string | null,
    services: [] as ServiceSelection[],
});

function submit() {
    form.post(route("tenant.documents.fee-calculator.attach.store", props.calculation.id));
}
</script>

<template>
    <Head title="Vincular à proposta" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-border pb-4">
        <h2 class="text-3xl font-bold tracking-tight text-foreground">Vincular à proposta</h2>
        <Button variant="outline" as-child>
            <Link :href="route('tenant.documents.fee-calculator.results.show', calculation.id)"><ChevronLeft class="mr-1 h-4 w-4" /> Voltar</Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl space-y-6">
        <form @submit.prevent="submit" class="space-y-6 rounded-lg border border-border bg-card p-6 shadow-sm">
            <Field>
                <FieldLabel for="proposal_id">Proposta (somente propostas sem emolumento vinculado) *</FieldLabel>
                <ComboboxRemote
                    id="proposal_id"
                    v-model="form.proposal_id"
                    :url="route('tenant.documents.fee-calculator.proposals.search')"
                    placeholder="Selecione a proposta..."
                    search-placeholder="Buscar por nº ou proponente..."
                    no-results-text="Nenhuma proposta disponível."
                />
                <FieldError v-if="form.errors.proposal_id">{{ form.errors.proposal_id }}</FieldError>
            </Field>

            <div>
                <h3 class="mb-3 text-lg font-semibold text-card-foreground">Serviços</h3>
                <ServicePicker v-model="form.services" :services="services" />
            </div>

            <div class="flex justify-end border-t border-border pt-6">
                <Button type="submit" :disabled="form.processing || !form.proposal_id">Gerar</Button>
            </div>
        </form>

        <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-card-foreground">
                {{ calculation.type.label }} ({{ calculation.state }} - {{ calculation.municipality_name }})
            </h3>
            <FeeResultTable :breakdown="calculation.breakdown" />
        </div>
    </div>
</template>
