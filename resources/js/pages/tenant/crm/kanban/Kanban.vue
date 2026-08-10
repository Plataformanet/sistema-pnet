<script setup lang="ts">
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import {
    Plus,
    Search,
    Filter,
    Kanban as KanbanIcon,
    ListFilter,
    DollarSign,
    TrendingUp,
    CheckCircle2,
    Calendar,
    Building2,
    MessageSquare,
    User,
    GripVertical,
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

// Subcomponentes do CRM
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

// Modais & Drawers
const isCreateModalOpen = ref(false);
const isDetailDrawerOpen = ref(false);
const selectedDeal = ref<CrmDeal | null>(null);

function formatMoney(cents: number): string {
    return new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(cents / 100);
}

const filteredDeals = computed(() => {
    return localDeals.value.filter((deal) => {
        const matchesPipeline = String(deal.crm_pipeline_id) === selectedPipelineId.value;
        const query = searchQuery.value.toLowerCase();
        const matchesSearch =
            !query ||
            deal.title.toLowerCase().includes(query) ||
            deal.contact_name.toLowerCase().includes(query) ||
            deal.user_name.toLowerCase().includes(query);

        return matchesPipeline && matchesSearch;
    });
});

function getDealsForStage(stageId: number): CrmDeal[] {
    return filteredDeals.value.filter((d) => d.crm_stage_id === stageId);
}

function getStageTotalValue(stageId: number): number {
    return getDealsForStage(stageId).reduce((acc, d) => acc + d.total_value, 0);
}

// Drag and Drop reativo
const draggedDealId = ref<number | null>(null);

function handleDragStart(event: DragEvent, dealId: number) {
    draggedDealId.value = dealId;
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = "move";
    }
}

function handleDragOver(event: DragEvent) {
    event.preventDefault();
}

function handleDropOnStage(targetStageId: number) {
    if (!draggedDealId.value) return;

    const dealIndex = localDeals.value.findIndex((d) => d.id === draggedDealId.value);
    if (dealIndex !== -1) {
        localDeals.value[dealIndex] = {
            ...localDeals.value[dealIndex],
            crm_stage_id: targetStageId,
        };
    }

    draggedDealId.value = null;
}

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
    <Head title="CRM - Funil de Vendas" />

    <div class="w-full max-w-full min-w-0 space-y-6 overflow-hidden">
        <!-- Header da Página -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-border pb-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-foreground flex items-center gap-2">
                   Funil de Vendas (CRM)
                </h1>
                <p class="text-sm text-muted-foreground mt-0.5">
                    Acompanhe suas propostas comerciais e a evolução das negociações.
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

        <!-- Dashboard de Métricas do Topo -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total em Aberto -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold tracking-wider text-muted-foreground uppercase">
                        Total em Aberto
                    </p>
                    <DollarSign class="h-4 w-4 text-primary" />
                </div>
                <p class="mt-2 text-2xl font-extrabold text-foreground">
                    {{ formatMoney(metrics.total_open_value) }}
                </p>
            </div>

            <!-- Vendas Ganhas Mês -->
            <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold tracking-wider text-emerald-600 dark:text-emerald-400 uppercase">
                        Vendas Ganhas (Mês)
                    </p>
                    <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                </div>
                <p class="mt-2 text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">
                    {{ formatMoney(metrics.won_this_month) }}
                </p>
            </div>

            <!-- Taxa de Conversão -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold tracking-wider text-muted-foreground uppercase">
                        Taxa de Conversão
                    </p>
                    <TrendingUp class="h-4 w-4 text-purple-500" />
                </div>
                <p class="mt-2 text-2xl font-extrabold text-foreground">
                    {{ metrics.conversion_rate }}%
                </p>
            </div>

            <!-- Negócios Ativos -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold tracking-wider text-muted-foreground uppercase">
                        Propostas Ativas
                    </p>
                    <Filter class="h-4 w-4 text-amber-500" />
                </div>
                <p class="mt-2 text-2xl font-extrabold text-foreground">
                    {{ metrics.active_deals_count }}
                </p>
            </div>
        </div>

        <!-- Barra de Filtros e Seleção do Funil -->
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
                            placeholder="Buscar por título, cliente ou vendedor..."
                            class="h-9 pl-9 text-xs"
                        />
                    </div>
                </div>

                <!-- Alternador Kanban / Lista -->
                <div class="flex items-center gap-1 rounded-lg border border-border bg-muted p-1">
                    <Button
                        variant="ghost"
                        size="sm"
                        class="h-7 px-3 text-xs font-bold bg-slate-900 text-white hover:bg-slate-900 hover:text-white dark:bg-white dark:text-slate-900 dark:hover:bg-white dark:hover:text-slate-900 shadow-xs cursor-pointer"
                    >
                        <KanbanIcon class="mr-1 h-3.5 w-3.5" /> Kanban
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        as-child
                        class="h-7 px-3 text-xs font-medium text-muted-foreground hover:bg-background/60 hover:text-foreground cursor-pointer"
                    >
                        <Link href="/crm/list">
                            <ListFilter class="mr-1 h-3.5 w-3.5" /> Tabela
                        </Link>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Quadro Kanban (Colunas de Estágios) -->
        <div class="w-full min-w-0 overflow-x-auto pb-4 pt-1 select-none">
            <div class="flex gap-4 w-max min-w-full">
                <div
                    v-for="stage in stages"
                    :key="stage.id"
                    @dragover="handleDragOver"
                    @drop="handleDropOnStage(stage.id)"
                    class="flex w-80 shrink-0 flex-col rounded-xl border border-border bg-muted/40 p-3"
                >
                    <!-- Cabeçalho da Coluna do Estágio -->
                    <div class="flex items-center justify-between border-b border-border/60 pb-3 mb-3">
                        <div class="flex items-center gap-2">
                            <span
                                class="h-3 w-3 rounded-full"
                                :style="{ backgroundColor: stage.color }"
                            ></span>
                            <h3 class="text-xs font-bold text-foreground">
                                {{ stage.name }}
                            </h3>
                            <span class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-bold text-muted-foreground">
                                {{ getDealsForStage(stage.id).length }}
                            </span>
                        </div>

                        <span class="text-xs font-bold text-primary">
                            {{ formatMoney(getStageTotalValue(stage.id)) }}
                        </span>
                    </div>

                    <!-- Cards de Negócios na Coluna -->
                    <div class="flex-1 space-y-3 overflow-y-auto min-h-[400px]">
                        <div
                            v-for="deal in getDealsForStage(stage.id)"
                            :key="deal.id"
                            draggable="true"
                            @dragstart="handleDragStart($event, deal.id)"
                            @click="openDetail(deal)"
                            class="group relative rounded-lg border border-border bg-card p-4 shadow-xs transition-all duration-150 hover:shadow-md hover:border-primary/50 cursor-pointer"
                        >
                            <!-- Topo do Card -->
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <h4 class="text-xs font-bold text-foreground group-hover:text-primary leading-snug">
                                    {{ deal.title }}
                                </h4>
                                <GripVertical class="h-4 w-4 shrink-0 text-muted-foreground/40 group-hover:text-muted-foreground" />
                            </div>

                            <!-- Cliente / Empresa -->
                            <p class="text-[11px] font-medium text-muted-foreground flex items-center gap-1 mb-3">
                                <Building2 class="h-3 w-3 shrink-0" /> {{ deal.contact_name }}
                            </p>

                            <!-- Footer do Card (Valor, Vendedor e Atividades) -->
                            <div class="flex items-center justify-between border-t border-border/50 pt-2.5 mt-2">
                                <span class="text-sm font-extrabold text-foreground">
                                    {{ formatMoney(deal.total_value) }}
                                </span>

                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="deal.activities && deal.activities.length > 0"
                                        class="flex items-center gap-1 text-[10px] text-muted-foreground font-semibold"
                                        title="Atividades registradas"
                                    >
                                        <MessageSquare class="h-3 w-3 text-primary" /> {{ deal.activities.length }}
                                    </span>

                                    <div
                                        class="flex h-6 w-6 items-center justify-center rounded-full bg-primary/10 text-[10px] font-bold text-primary border border-primary/20"
                                        :title="`Vendedor: ${deal.user_name}`"
                                    >
                                        {{ deal.user_name.slice(0, 2).toUpperCase() }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="getDealsForStage(stage.id).length === 0"
                            class="py-12 text-center text-xs text-muted-foreground/60 border border-dashed border-border/80 rounded-lg"
                        >
                            Arraste um negócio para cá
                        </div>
                    </div>
                </div>
            </div>
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
