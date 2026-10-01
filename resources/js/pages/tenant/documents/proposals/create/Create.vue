<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import ProposalForm from "../components/ProposalForm.vue";
import type { ProposalFormOptions } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<ProposalFormOptions>();

const form = useForm({
    creator_id: null as number | null,
    analyst_id: null as number | null,
    partner_ids: [] as number[],
    bank_id: null as number | null,
    contract_type_id: null as number | null,
    amortization_table: "",
    payment_term: null as number | null,
    property_condition: "used",
    has_other_property: false,
    purchase_value: null as number | null,
    down_payment_value: null as number | null,
    financing_value: null as number | null,
    expenses_value: null as number | null,
    subsidy_value: null as number | null,
    financed_value: null as number | null,
    intended_installment_value: null as number | null,
    fgts_value: null as number | null,
    uses_fgts: false,
    is_first_financing: false,
    finance_documentation_fee: false,
    documentation_fee_to_finance: null as number | null,
    declares_income_tax: false,
    declared_income: null as number | null,
    contract_notes: "",
    purchase_value_notes: "",
    down_payment_notes: "",
    fgts_notes: "",
    documentation_fee_notes: "",
    documentation_financing_notes: "",
    income_tax_notes: "",
    general_notes: "",
    property: {
        property_type_id: null as number | null,
        development_id: null as number | null,
        address: "",
        number: "",
        complement: "",
        block: "",
        unit: "",
    },
    applicants: [] as Record<string, any>[],
    sellers: [] as Record<string, any>[],
});

function submit() {
    form.post(route("tenant.documents.proposals.store"));
}
</script>

<template>
    <Head title="Nova Proposta" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">Nova Proposta</h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.proposals.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <ProposalForm :form="form" :options="props" @submit="submit" submit-text="Criar Proposta" />
    </div>
</template>
