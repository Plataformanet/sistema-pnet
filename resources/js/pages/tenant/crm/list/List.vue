<script setup lang="ts">
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import {
    Plus,
    Search,
    Kanban as KanbanIcon,
    ListFilter,
    Building2,
    DollarSign,
    CheckCircle2,
    XCircle,
    Eye,
    MoreHorizontal,
    User,
} from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import type { CrmPipeline, CrmStage, CrmMetrics, CrmDeal } from "@/types";

import DealFormModal from "../components/DealFormModal.vue";
import DealDetailDrawer from "../components/DealDetailDrawer.vue";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    pipelines: CrmPipeline[];
    stages: CrmStage[];
    metrics: CrmMetrics;
    deals: CrmDeal[];
}>();

const localDeals = ref<CrmDeal[]>([...props.deals]);
const selectedPipelineId = ref<string>(String(props.pipelines[0]?.id || 1));
const searchQuery = ref("");
const statusFilter = ref<"all" | "open" | "won" | "lost">("all");

const isCreateModalOpen = ref(false);
const isDetailDrawerOpen = ref(false);
const selectedDeal = ref<CrmDeal | null>(null);

function formatMoney(cents: number): string {
    return new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(cents / 100);
}

function getStageName(stageId: number): string {
    return props.stages.find((s) => s.id === stageId)?.name || "N/A";
}

function getStageColor(stageId: number): string {
    return props.stages.find((s) => s.id === stageId)?.color || "#64748b";
}

const filteredDeals = computed(() => {
    return localDeals.value.filter((deal) => {
        const matchesPipeline = String(deal.crm_pipeline_id) === selectedPipelineId.value;
        const matchesStatus = statusFilter.value === "all" || deal.status === statusFilter.value;

        const query = searchQuery.value.toLowerCase();
        const matchesSearch =
            !query ||
            deal.title.toLowerCase().includes(query) ||
            deal.contact_name.toLowerCase().includes(query) ||
            deal.user_name.toLowerCase().includes(query);

        return matchesPipeline && matchesStatus && matchesSearch;
    });
});

function openDetail(deal: CrmDeal) {
    selectedDeal.value = deal;
    isDetailDrawerOpen.value = true;
}

function handleSaveNewDeal(newDealData: any) {
    const newDeal: CrmDeal = {
        id: Date.now(),
        title: newDealData.title,
        contact_id: 99,
        contact_name: newDealData.contact_name,
        user_id: 1,
        user_name: "Carlos Eduardo",
        crm_pipeline_id: newDealData.crm_pipeline_id,
        crm_stage_id: newDealData.crm_stage_id,
        total_value: newDealData.total_value,
        status: "open",
        priority: newDealData.priority,
        created_at: new Date().toISOString(),
        items: [],
        activities: [],
    };

    localDeals.value.unshift(newDeal);
}

function handleUpdateDeal(updatedDeal: CrmDeal) {
    const idx = localDeals.value.findIndex((d) => d.id === updatedDeal.id);
    if (idx !== -1) {
        localDeals.value[idx] = updatedDeal;
    }
    selectedDeal.value = updatedDeal;
}
</script>

<template>
    <Head title="CRM - Tabela de Propostas" />

    <div class="space-y-6">
        <!-- Header da Página -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-border pb-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-foreground flex items-center gap-2">
                    Listagem de Propostas (CRM)
                </h1>
                <p class="text-sm text-muted-foreground mt-0.5">
                    Visão em lista estruturada de todas as suas propostas comerciais.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Button
                    @click="isCreateModalOpen = true"
                    class="cursor-pointer font-semibold shadow-xs"
                >
                    <Plus class="mr-1.5 h-4 w-4" /> Nova Proposta
                </Button>
            </div>
        </div>

        <!-- Barra de Filtros -->
        <div class="rounded-xl border border-border bg-card p-4 shadow-xs">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="w-64">
                        <Select v-model="selectedPipelineId">
                            <SelectTrigger class="h-9 border-border bg-background text-xs font-semibold">
                                <SelectValue placeholder="Selecione o Funil" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="p in pipelines"
                                    :key="p.id"
                                    :value="String(p.id)"
                                >
                                    {{ p.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="relative w-72">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Buscar por proposta, cliente..."
                            class="h-9 pl-9 text-xs"
                        />
                    </div>
                </div>

                <!-- Alternador Kanban / Lista -->
                <div class="flex items-center gap-1 rounded-lg border border-border bg-muted p-1">
                    <Button
                        variant="ghost"
                        size="sm"
                        as-child
                        class="h-7 px-3 text-xs font-medium text-muted-foreground hover:bg-background/60 hover:text-foreground cursor-pointer"
                    >
                        <Link href="/crm/kanban">
                            <KanbanIcon class="mr-1 h-3.5 w-3.5" /> Kanban
                        </Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="h-7 px-3 text-xs font-bold bg-slate-900 text-white hover:bg-slate-900 hover:text-white dark:bg-white dark:text-slate-900 dark:hover:bg-white dark:hover:text-slate-900 shadow-xs cursor-pointer"
                    >
                        <ListFilter class="mr-1 h-3.5 w-3.5" /> Tabela
                    </Button>
                </div>
            </div>
        </div>

        <!-- Tabela de Propostas -->
        <div class="overflow-hidden rounded-xl border border-border bg-card shadow-xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-border bg-muted/60 text-muted-foreground font-semibold">
                        <th class="p-4">Proposta Comercial</th>
                        <th class="p-4">Cliente / Empresa</th>
                        <th class="p-4">Vendedor</th>
                        <th class="p-4 text-center">Estágio Atual</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-right">Valor Total</th>
                        <th class="p-4 text-center w-20">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="deal in filteredDeals"
                        :key="deal.id"
                        class="hover:bg-muted/30 transition-colors cursor-pointer"
                        @click="openDetail(deal)"
                    >
                        <td class="p-4 font-bold text-foreground">
                            {{ deal.title }}
                        </td>
                        <td class="p-4 font-medium text-muted-foreground">
                            <span class="flex items-center gap-1.5">
                                <Building2 class="h-3.5 w-3.5 text-primary" /> {{ deal.contact_name }}
                            </span>
                        </td>
                        <td class="p-4 text-muted-foreground">
                            {{ deal.user_name }}
                        </td>
                        <td class="p-4 text-center">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :style="{ backgroundColor: getStageColor(deal.crm_stage_id) + '20', color: getStageColor(deal.crm_stage_id) }"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: getStageColor(deal.crm_stage_id) }"></span>
                                {{ getStageName(deal.crm_stage_id) }}
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <span
                                v-if="deal.status === 'won'"
                                class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-bold text-emerald-500 inline-flex items-center gap-1"
                            >
                                <CheckCircle2 class="h-3.5 w-3.5" /> Ganho
                            </span>
                            <span
                                v-else-if="deal.status === 'lost'"
                                class="rounded-full bg-rose-500/10 px-2.5 py-0.5 text-xs font-bold text-rose-500 inline-flex items-center gap-1"
                            >
                                <XCircle class="h-3.5 w-3.5" /> Perdido
                            </span>
                            <span
                                v-else
                                class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary"
                            >
                                Aberto
                            </span>
                        </td>
                        <td class="p-4 text-right font-extrabold text-foreground font-mono text-sm">
                            {{ formatMoney(deal.total_value) }}
                        </td>
                        <td class="p-4 text-center" @click.stop>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="h-8 w-8 p-0 cursor-pointer"
                                @click="openDetail(deal)"
                            >
                                <Eye class="h-4 w-4 text-muted-foreground hover:text-primary" />
                            </Button>
                        </td>
                    </tr>

                    <tr v-if="filteredDeals.length === 0">
                        <td colspan="7" class="py-12 text-center text-xs text-muted-foreground">
                            Nenhuma oportunidade encontrada para os filtros selecionados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modais & Drawers -->
        <DealFormModal
            v-model:is-open="isCreateModalOpen"
            :pipelines="pipelines"
            :stages="stages"
            @save="handleSaveNewDeal"
        />

        <DealDetailDrawer
            v-model:is-open="isDetailDrawerOpen"
            :deal="selectedDeal"
            :stages="stages"
            @update-deal="handleUpdateDeal"
        />
    </div>
</template>
