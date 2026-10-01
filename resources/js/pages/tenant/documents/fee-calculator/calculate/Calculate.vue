<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import FeeCalculatorForm from "../components/FeeCalculatorForm.vue";
import type { FeeCalculatorFormData, FeeDiscountOption, Option } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    type: { value: number; label: string; description: string };
    state: string | null;
    municipality: { ibge_code: number | null; name: string | null; itbi_module: string | null };
    financingSystems: Option[];
    discounts: FeeDiscountOption[];
    commonNotes: string;
}>();

const form = useForm<FeeCalculatorFormData>({
    state: props.state,
    municipality_ibge_code: props.municipality.ibge_code,
    municipality_name: props.municipality.name,
    type: props.type.value,
    property_value: null,
    financing_value: null,
    financing_system: "sfh",
    first_property: null,
    discount: null,
});

function submit() {
    form.post(route("tenant.documents.fee-calculator.store"));
}
</script>

<template>
    <Head :title="type.label" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-border pb-4">
        <h2 class="text-2xl font-semibold tracking-tight text-foreground">
            {{ type.label }} ({{ state }} - {{ municipality.name }})
        </h2>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.fee-calculator.index', { state: state ?? undefined, ibge: municipality.ibge_code ?? undefined })">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-7xl rounded-lg border border-border bg-card p-6 shadow-sm">
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
    </div>
</template>
