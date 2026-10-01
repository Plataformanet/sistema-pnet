<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import QuoteForm from "../components/QuoteForm.vue";
import FeeResultTable from "@/pages/tenant/documents/fee-calculator/components/FeeResultTable.vue";
import type { BillableServiceOption, FeeCalculationSummary, Option, ServiceSelection } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    calculation: FeeCalculationSummary;
    banks: Array<{ id: number; name: string }>;
    services: BillableServiceOption[];
    maritalStatuses: Option<number>[];
}>();

const form = useForm({
    name: "",
    cpf: "",
    email: "",
    phone: "",
    profession: "",
    marital_status: null as number | null,
    bank_id: null as number | null,
    valid_until: "",
    services: [] as ServiceSelection[],
});

function submit() {
    form.post(route("tenant.documents.quotes.store", props.calculation.id));
}
</script>

<template>
    <Head title="Novo Orçamento" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <h2 class="text-3xl font-bold tracking-tight text-foreground">Novo Orçamento</h2>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.fee-calculator.results.show', calculation.id)"><ChevronLeft class="mr-2 h-4 w-4" /> Voltar</Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl space-y-6">
        <QuoteForm :form="form" :banks="banks" :services="services" :marital-statuses="maritalStatuses" submit-text="Gerar Orçamento" @submit="submit" />

        <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-card-foreground">
                Resumo do cálculo — {{ calculation.type.label }} ({{ calculation.state }} - {{ calculation.municipality_name }})
            </h3>
            <FeeResultTable :breakdown="calculation.breakdown" />
        </div>
    </div>
</template>
