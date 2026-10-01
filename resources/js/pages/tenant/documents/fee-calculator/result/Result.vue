<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft, FileText, Link2 } from "lucide-vue-next";
import { route } from "ziggy-js";
import { usePermission } from "@/composables/usePermission";
import FeeCalculatorForm from "../components/FeeCalculatorForm.vue";
import FeeResultTable from "../components/FeeResultTable.vue";
import type { FeeCalculationSummary, FeeCalculatorFormData, FeeDiscountOption, Option } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    calculation: FeeCalculationSummary;
    type: { value: number; label: string; description: string };
    state: string | null;
    municipality: { ibge_code: number | null; name: string | null; itbi_module: string | null };
    financingSystems: Option[];
    discounts: FeeDiscountOption[];
    commonNotes: string;
}>();

const { permissions } = usePermission();

/** Formulário já preenchido com a entrada do cálculo: recalcular gera um novo resultado. */
const input = props.calculation.input as Partial<FeeCalculatorFormData>;

const form = useForm<FeeCalculatorFormData>({
    state: props.state,
    municipality_ibge_code: props.municipality.ibge_code,
    municipality_name: props.municipality.name,
    type: props.type.value,
    property_value: input.property_value ?? null,
    financing_value: input.financing_value ?? null,
    financing_system: input.financing_system ?? "sfh",
    first_property: input.first_property ?? null,
    discount: input.discount ?? null,
});

function submit() {
    form.post(route("tenant.documents.fee-calculator.store"));
}
</script>

<template>
    <Head title="Resultado do cálculo" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-border pb-4">
        <h2 class="text-2xl font-semibold tracking-tight text-foreground">
            {{ type.label }} ({{ state }} - {{ municipality.name }})
        </h2>
        <div class="flex flex-wrap gap-2">
            <Button variant="outline" as-child>
                <Link :href="route('tenant.documents.fee-calculator.index', { state: state ?? undefined, ibge: municipality.ibge_code ?? undefined })"
                    ><ChevronLeft class="mr-1 h-4 w-4" /> Voltar</Link
                >
            </Button>
            <Button v-if="permissions.includes('documents.itbi_calculator.create')" variant="outline" as-child>
                <Link :href="route('tenant.documents.fee-calculator.attach.create', calculation.id)"><Link2 class="mr-1 h-4 w-4" /> Vincular à proposta</Link>
            </Button>
            <Button v-if="permissions.includes('documents.quotes.create')" as-child>
                <Link :href="route('tenant.documents.quotes.create', calculation.id)"><FileText class="mr-1 h-4 w-4" /> Gerar orçamento</Link>
            </Button>
        </div>
    </div>

    <div class="mx-auto mb-20 max-w-7xl space-y-10 rounded-lg border border-border bg-card p-6 shadow-sm">
        <FeeCalculatorForm
            :form="form"
            :type="type"
            :state="state"
            :municipality="municipality"
            :financing-systems="financingSystems"
            :discounts="discounts"
            :common-notes="commonNotes"
            @submit="submit"
        />

        <section class="space-y-4">
            <h3 class="text-xl font-semibold text-foreground">Resultado</h3>
            <FeeResultTable :breakdown="calculation.breakdown" />
        </section>
    </div>
</template>
