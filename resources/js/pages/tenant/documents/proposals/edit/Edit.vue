<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import FieldError from "@/components/ui/field/FieldError.vue";
import { ChevronLeft, FileText } from "lucide-vue-next";
import { route } from "ziggy-js";
import { computed, ref } from "vue";
import ProposalForm from "../components/ProposalForm.vue";
import DocumentsTab from "../components/DocumentsTab.vue";
import TimelineTab from "../components/TimelineTab.vue";
import CostItemsTab from "../components/CostItemsTab.vue";
import ReceiptsTab from "../components/ReceiptsTab.vue";
import type {
    ChecklistItem,
    CostType,
    Notary,
    Option,
    Proposal,
    ProposalAbilities,
    ProposalFormOptions,
    StatusOption,
} from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<
    ProposalFormOptions & {
        proposal: Proposal;
        checklist: ChecklistItem[];
        feesTotal: number;
        can: ProposalAbilities;
        costTypes: Pick<CostType, "id" | "name" | "requires_notary" | "receipt_type">[];
        notaries: Pick<Notary, "id" | "name">[];
        statuses: StatusOption[];
        documentTypes: Record<string, Option[]>;
        maxUploadKb: number;
    }
>();

const options = computed<ProposalFormOptions>(() => ({
    banks: props.banks,
    contractTypes: props.contractTypes,
    propertyTypes: props.propertyTypes,
    developments: props.developments,
    staff: props.staff,
    partners: props.partners,
    maritalStatuses: props.maritalStatuses,
    amortizationTables: props.amortizationTables,
    propertyConditions: props.propertyConditions,
    personTypes: props.personTypes,
    bankAccountTypes: props.bankAccountTypes,
}));

type Tab = "info" | "documents" | "particularities" | "timeline" | "financial" | "receipts";

// O parceiro abre a proposta em modo leitura, já na aba de documentos.
const activeTab = ref<Tab>(props.can.update ? "info" : "documents");

const tabs = computed(() =>
    [
        { key: "info", label: "Informações", visible: true },
        { key: "documents", label: "Documentos", visible: true },
        { key: "particularities", label: "Particularidades", visible: props.can.update },
        { key: "timeline", label: "Acompanhamento", visible: true },
        { key: "financial", label: "Pagamentos e Taxas", visible: props.can.viewFinancial || props.can.uploadProof },
        { key: "receipts", label: "Recibos", visible: props.can.viewFinancial },
    ].filter((tab) => tab.visible) as Array<{ key: Tab; label: string }>,
);

const p = props.proposal;

const form = useForm({
    status: p.status,
    cancellation_reason: p.cancellation_reason ?? "",
    restriction_reason: p.restriction_reason ?? "",
    expected_delivery_month: p.expected_delivery_month ?? null,
    expected_delivery_year: p.expected_delivery_year ?? null,
    creator_id: p.creator_id,
    analyst_id: p.analyst_id === p.creator_id ? null : p.analyst_id,
    partner_ids: (p.partners ?? []).map((partner) => partner.id),
    bank_id: p.bank_id,
    contract_type_id: p.contract_type_id,
    amortization_table: p.amortization_table ?? "",
    payment_term: p.payment_term ?? null,
    property_condition: p.property_condition,
    has_other_property: p.has_other_property,
    purchase_value: p.purchase_value,
    down_payment_value: p.down_payment_value,
    financing_value: p.financing_value ?? null,
    expenses_value: p.expenses_value ?? null,
    subsidy_value: p.subsidy_value ?? null,
    financed_value: p.financed_value ?? null,
    intended_installment_value: p.intended_installment_value ?? null,
    fgts_value: p.fgts_value ?? null,
    uses_fgts: p.uses_fgts,
    is_first_financing: p.is_first_financing,
    finance_documentation_fee: p.finance_documentation_fee,
    documentation_fee_to_finance: p.documentation_fee_to_finance ?? null,
    declares_income_tax: p.declares_income_tax,
    declared_income: p.declared_income ?? null,
    contract_notes: p.contract_notes ?? "",
    purchase_value_notes: p.purchase_value_notes ?? "",
    down_payment_notes: p.down_payment_notes ?? "",
    fgts_notes: p.fgts_notes ?? "",
    documentation_fee_notes: p.documentation_fee_notes ?? "",
    documentation_financing_notes: p.documentation_financing_notes ?? "",
    income_tax_notes: p.income_tax_notes ?? "",
    general_notes: p.general_notes ?? "",
    property: {
        property_type_id: p.property?.property_type_id ?? null,
        development_id: p.property?.development_id ?? null,
        address: p.property?.address ?? "",
        number: p.property?.number ?? "",
        complement: p.property?.complement ?? "",
        block: p.property?.block ?? "",
        unit: p.property?.unit ?? "",
    },
});

function submit() {
    form.put(route("tenant.documents.proposals.update", p.id), { preserveScroll: true });
}

const particularitiesForm = useForm({ particularities: p.particularities ?? "" });

function saveParticularities() {
    particularitiesForm.patch(route("tenant.documents.proposals.particularities.update", p.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Proposta Nº ${proposal.number}`" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">Proposta Nº {{ proposal.number }}</h2>
            <p class="text-sm text-muted-foreground">
                {{ (proposal.applicants ?? []).map((applicant) => applicant.contact.name_corporatereason).join(", ") }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <Button v-if="can.print" variant="outline" as-child>
                <a :href="route('tenant.documents.proposals.pdf.info', proposal.id)" target="_blank"><FileText class="mr-1 h-4 w-4" /> Informativo</a>
            </Button>
            <Button v-if="can.print" variant="outline" as-child>
                <a :href="route('tenant.documents.proposals.pdf.tracking', proposal.id)" target="_blank"><FileText class="mr-1 h-4 w-4" /> Acompanhamento</a>
            </Button>
            <Button variant="outline" class="cursor-pointer" as-child>
                <Link :href="route('tenant.documents.proposals.list')"><ChevronLeft class="mr-2 h-4 w-4" /> Voltar</Link>
            </Button>
        </div>
    </div>

    <div class="mb-6 flex flex-wrap gap-1 border-b border-border">
        <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            class="-mb-px border-b-2 px-4 py-2 text-sm font-medium transition-colors"
            :class="activeTab === tab.key ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'"
            @click="activeTab = tab.key"
        >
            {{ tab.label }}
        </button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl">
        <ProposalForm
            v-if="activeTab === 'info'"
            :form="form"
            :options="options"
            :with-people="false"
            :statuses="statuses"
            :finished-at="proposal.finished_at"
            :readonly="!can.update"
            submit-text="Atualizar Proposta"
            @submit="submit"
        />

        <div v-else-if="activeTab === 'documents'" class="rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8">
            <DocumentsTab :proposal="proposal" :checklist="checklist" :document-types="documentTypes" :max-upload-kb="maxUploadKb" :can="can" />
        </div>

        <form
            v-else-if="activeTab === 'particularities'"
            class="space-y-4 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8"
            @submit.prevent="saveParticularities"
        >
            <h3 class="text-lg font-semibold text-card-foreground">Particularidades</h3>
            <Textarea v-model="particularitiesForm.particularities" rows="10" />
            <FieldError v-if="particularitiesForm.errors.particularities">{{ particularitiesForm.errors.particularities }}</FieldError>
            <div class="flex justify-end">
                <Button type="submit" :disabled="particularitiesForm.processing">Salvar particularidades</Button>
            </div>
        </form>

        <div v-else-if="activeTab === 'timeline'" class="rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8">
            <TimelineTab :proposal="proposal" :can="can" />
        </div>

        <div v-else-if="activeTab === 'financial'" class="rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8">
            <CostItemsTab :proposal="proposal" :cost-types="costTypes" :notaries="notaries" :fees-total="feesTotal" :can="can" />
        </div>

        <div v-else-if="activeTab === 'receipts'" class="rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8">
            <ReceiptsTab :proposal="proposal" :can="can" />
        </div>
    </div>
</template>
