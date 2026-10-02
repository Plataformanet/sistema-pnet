<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft, FileText, Pencil } from "lucide-vue-next";
import { route } from "ziggy-js";
import { formatMoney } from "@/lib/masks";
import DocumentsTab from "../components/DocumentsTab.vue";
import TimelineTab from "../components/TimelineTab.vue";
import CostItemsTab from "../components/CostItemsTab.vue";
import type { ChecklistItem, Proposal, ProposalAbilities } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    proposal: Proposal;
    checklist: ChecklistItem[];
    feesTotal: number;
    can: ProposalAbilities;
}>();

// A visualização nunca mostra formulários de gestão: só leitura, download e
// o envio de comprovante pelo proponente.
const readOnly: ProposalAbilities = {
    ...props.can,
    update: false,
    delete: false,
    manageTimeline: false,
    manageFinancial: false,
    uploadDocument: false,
    deleteDocument: false,
};

const conditionLabels: Record<string, string> = { new: "Novo", used: "Usado" };
</script>

<template>
    <Head :title="`Proposta Nº ${proposal.number}`" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-border pb-4">
        <h2 class="text-3xl font-bold tracking-tight text-foreground">Proposta Nº {{ proposal.number }}</h2>
        <div class="flex flex-wrap gap-2">
            <Button v-if="can.print" variant="outline" as-child>
                <a :href="route('tenant.documents.proposals.pdf.info', proposal.id)" target="_blank"><FileText class="mr-1 h-4 w-4" /> Informativo</a>
            </Button>
            <Button v-if="can.print" variant="outline" as-child>
                <a :href="route('tenant.documents.proposals.pdf.tracking', proposal.id)" target="_blank"><FileText class="mr-1 h-4 w-4" /> Acompanhamento</a>
            </Button>
            <Button v-if="can.update" variant="outline" as-child>
                <Link :href="route('tenant.documents.proposals.edit', proposal.id)"><Pencil class="mr-1 h-4 w-4" /> Editar</Link>
            </Button>
            <Button variant="outline" class="cursor-pointer" as-child>
                <Link :href="route('tenant.documents.proposals.list')"><ChevronLeft class="mr-2 h-4 w-4" /> Voltar</Link>
            </Button>
        </div>
    </div>

    <div class="mx-auto mb-20 max-w-6xl space-y-6">
        <section class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-card-foreground">Resumo</h3>
            <dl class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                <div><dt class="text-muted-foreground">Banco</dt><dd>{{ proposal.bank?.name }}</dd></div>
                <div><dt class="text-muted-foreground">Contrato</dt><dd>{{ proposal.contract_type?.name }}</dd></div>
                <div><dt class="text-muted-foreground">Imóvel</dt><dd>{{ conditionLabels[proposal.property_condition] }}</dd></div>
                <div><dt class="text-muted-foreground">Valor de compra</dt><dd>{{ formatMoney(proposal.purchase_value) }}</dd></div>
                <div><dt class="text-muted-foreground">Entrada</dt><dd>{{ formatMoney(proposal.down_payment_value) }}</dd></div>
                <div><dt class="text-muted-foreground">Criador</dt><dd>{{ proposal.creator?.name }}</dd></div>
                <div><dt class="text-muted-foreground">Analista</dt><dd>{{ proposal.analyst?.name ?? "Não definido" }}</dd></div>
                <div class="md:col-span-3">
                    <dt class="text-muted-foreground">Proponentes</dt>
                    <dd>{{ (proposal.applicants ?? []).map((applicant) => applicant.contact.name_corporatereason).join(", ") }}</dd>
                </div>
                <div v-if="proposal.sellers?.length" class="md:col-span-3">
                    <dt class="text-muted-foreground">Vendedores</dt>
                    <dd>{{ proposal.sellers.map((seller) => seller.contact.name_corporatereason).join(", ") }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-card-foreground">Acompanhamento</h3>
            <TimelineTab :proposal="proposal" :can="readOnly" />
        </section>

        <section class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-card-foreground">Documentos</h3>
            <DocumentsTab :proposal="proposal" :checklist="checklist" :document-types="{}" :max-upload-kb="0" :can="readOnly" />
        </section>

        <section v-if="can.viewFinancial || can.uploadProof" class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-card-foreground">Pagamentos e Taxas</h3>
            <CostItemsTab :proposal="proposal" :cost-types="[]" :notaries="[]" :fees-total="feesTotal" :can="readOnly" />
        </section>
    </div>
</template>
