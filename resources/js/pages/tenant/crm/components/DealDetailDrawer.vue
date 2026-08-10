<script setup lang="ts">
import { ref, computed } from "vue";
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from "@/components/ui/sheet";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Building2,
    Calendar,
    Phone,
    User,
    Plus,
    Trash2,
    CheckCircle2,
    XCircle,
    Package,
    Wrench,
    MessageSquare,
    PhoneCall,
    CalendarCheck,
    Clock,
    FileText,
} from "lucide-vue-next";
import type { CrmDeal, CrmStage, CrmDealItem } from "@/types";

const props = defineProps<{
    isOpen: boolean;
    deal: CrmDeal | null;
    stages: CrmStage[];
}>();

const emit = defineEmits<{
    (e: "update:isOpen", value: boolean): void;
    (e: "update-deal", updatedDeal: CrmDeal): void;
}>();

const activeTab = ref<"overview" | "items" | "history">("overview");
const newActivityText = ref("");
const newActivityType = ref<"note" | "call" | "meeting">("note");

// Modal de adição de produto/serviço
const showAddItem = ref(false);
const newItemType = ref<"product" | "service">("service");
const newItemName = ref("");
const newItemPrice = ref<number | string>("");
const newItemQty = ref<number>(1);

function formatMoney(cents?: number): string {
    if (cents === undefined || cents === null) return "R$ 0,00";
    return new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(cents / 100);
}

const currentStage = computed(() => {
    if (!props.deal) return null;
    return props.stages.find((s) => s.id === props.deal?.crm_stage_id);
});

function handleStageChange(newStageIdStr: string) {
    if (!props.deal) return;
    const updated = { ...props.deal, crm_stage_id: Number(newStageIdStr) };
    emit("update-deal", updated);
}

function handleStatusChange(newStatus: "open" | "won" | "lost") {
    if (!props.deal) return;
    const updated = { ...props.deal, status: newStatus };
    emit("update-deal", updated);
}

function addActivity() {
    if (!props.deal || !newActivityText.value.trim()) return;

    const newAct = {
        id: Date.now(),
        type: newActivityType.value,
        description: newActivityText.value,
        user_name: props.deal.user_name || "Você",
        created_at: new Date().toISOString(),
    };

    const activities = [newAct, ...(props.deal.activities || [])];
    const updated = { ...props.deal, activities };
    emit("update-deal", updated);
    newActivityText.value = "";
}

function addItem() {
    if (!props.deal || !newItemName.value.trim() || !newItemPrice.value) return;

    const priceCents = Number(newItemPrice.value) * 100;
    const qty = Number(newItemQty.value) || 1;
    const totalPrice = priceCents * qty;

    const newItem: CrmDealItem = {
        id: Date.now(),
        item_type: newItemType.value,
        name: newItemName.value,
        unit_price: priceCents,
        quantity: qty,
        discount: 0,
        total_price: totalPrice,
    };

    const items = [...(props.deal.items || []), newItem];
    const newTotal = items.reduce((acc, curr) => acc + curr.total_price, 0);

    const updated = { ...props.deal, items, total_value: newTotal };
    emit("update-deal", updated);

    // Reset
    newItemName.value = "";
    newItemPrice.value = "";
    newItemQty.value = 1;
    showAddItem.value = false;
}

function removeItem(itemId: number) {
    if (!props.deal) return;
    const items = (props.deal.items || []).filter((i) => i.id !== itemId);
    const newTotal = items.reduce((acc, curr) => acc + curr.total_price, 0);
    const updated = { ...props.deal, items, total_value: newTotal };
    emit("update-deal", updated);
}
</script>

<template>
    <Sheet :open="isOpen" @update:open="emit('update:isOpen', $event)">
        <SheetContent
            side="right"
            class="w-full max-w-2xl sm:max-w-2xl p-0 overflow-y-auto bg-card text-card-foreground border-l border-border"
        >
            <div v-if="deal" class="flex flex-col h-full divide-y divide-border">
                <!-- Header do Drawer -->
                <div class="p-6 space-y-4 bg-muted/30">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    v-if="deal.status === 'won'"
                                    class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-bold text-emerald-500 flex items-center gap-1"
                                >
                                    <CheckCircle2 class="h-3.5 w-3.5" /> Venda Ganha
                                </span>
                                <span
                                    v-else-if="deal.status === 'lost'"
                                    class="rounded-full bg-rose-500/10 px-2.5 py-0.5 text-xs font-bold text-rose-500 flex items-center gap-1"
                                >
                                    <XCircle class="h-3.5 w-3.5" /> Venda Perdida
                                </span>
                                <span
                                    v-else
                                    class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary"
                                >
                                    Em Andamento
                                </span>

                                <span
                                    class="rounded-full border border-border bg-background px-2.5 py-0.5 text-xs font-semibold uppercase text-muted-foreground"
                                >
                                    Prioridade {{ deal.priority }}
                                </span>
                            </div>

                            <h2 class="text-xl font-bold text-foreground leading-tight">
                                {{ deal.title }}
                            </h2>

                            <div class="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
                                <span class="flex items-center gap-1">
                                    <Building2 class="h-3.5 w-3.5" /> {{ deal.contact_name }}
                                </span>
                                <span v-if="deal.contact_phone" class="flex items-center gap-1">
                                    <Phone class="h-3.5 w-3.5" /> {{ deal.contact_phone }}
                                </span>
                            </div>
                        </div>

                        <!-- Valor em Destaque -->
                        <div class="text-right shrink-0">
                            <span class="text-xs font-semibold text-muted-foreground uppercase block">
                                Valor Total
                            </span>
                            <span class="text-2xl font-extrabold text-primary">
                                {{ formatMoney(deal.total_value) }}
                            </span>
                        </div>
                    </div>

                    <!-- Controles de Estágio e Status -->
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div class="space-y-1">
                            <Label class="text-[11px] font-bold text-muted-foreground uppercase">
                                Estágio Atual
                            </Label>
                            <Select
                                :model-value="String(deal.crm_stage_id)"
                                @update:model-value="(val: any) => handleStageChange(String(val))"
                            >
                                <SelectTrigger class="h-9 bg-background text-xs font-semibold">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="s in stages"
                                        :key="s.id"
                                        :value="String(s.id)"
                                    >
                                        {{ s.name }} ({{ s.probability }}%)
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1">
                            <Label class="text-[11px] font-bold text-muted-foreground uppercase">
                                Marcar Resultado
                            </Label>
                            <div class="flex items-center gap-1.5 pt-0.5">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    class="h-8 text-xs flex-1 cursor-pointer"
                                    :class="deal.status === 'won' ? 'bg-emerald-500 text-white hover:bg-emerald-600 border-emerald-500' : 'hover:bg-emerald-500/10 hover:text-emerald-500'"
                                    @click="handleStatusChange('won')"
                                >
                                    <CheckCircle2 class="mr-1 h-3.5 w-3.5" /> Ganho
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    class="h-8 text-xs flex-1 cursor-pointer"
                                    :class="deal.status === 'lost' ? 'bg-rose-500 text-white hover:bg-rose-600 border-rose-500' : 'hover:bg-rose-500/10 hover:text-rose-500'"
                                    @click="handleStatusChange('lost')"
                                >
                                    <XCircle class="mr-1 h-3.5 w-3.5" /> Perdido
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Abas de Navegação -->
                <div class="flex border-b border-border bg-card px-6">
                    <button
                        @click="activeTab = 'overview'"
                        class="px-4 py-3 text-xs font-semibold border-b-2 cursor-pointer transition-colors"
                        :class="activeTab === 'overview' ? 'border-primary text-primary font-bold' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    >
                        Visão Geral & Timeline
                    </button>
                    <button
                        @click="activeTab = 'items'"
                        class="px-4 py-3 text-xs font-semibold border-b-2 cursor-pointer transition-colors flex items-center gap-1.5"
                        :class="activeTab === 'items' ? 'border-primary text-primary font-bold' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    >
                        Itens da Proposta ({{ deal.items?.length || 0 }})
                    </button>
                    <button
                        @click="activeTab = 'history'"
                        class="px-4 py-3 text-xs font-semibold border-b-2 cursor-pointer transition-colors"
                        :class="activeTab === 'history' ? 'border-primary text-primary font-bold' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    >
                        Histórico
                    </button>
                </div>

                <!-- Conteúdo das Abas -->
                <div class="flex-1 p-6 space-y-6">
                    <!-- ABA 1: Visão Geral / Timeline -->
                    <div v-if="activeTab === 'overview'" class="space-y-6">
                        <!-- Adicionar Nova Atividade -->
                        <div class="rounded-xl border border-border bg-muted/40 p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <Label class="text-xs font-bold uppercase text-foreground flex items-center gap-1.5">
                                    <MessageSquare class="h-3.5 w-3.5 text-primary" /> Registrar Atividade / Nota
                                </Label>

                                <div class="flex items-center gap-1 rounded-md bg-muted p-0.5">
                                    <button
                                        @click="newActivityType = 'note'"
                                        class="px-2 py-1 text-[11px] font-semibold rounded cursor-pointer transition-all"
                                        :class="newActivityType === 'note' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground'"
                                    >
                                        Nota
                                    </button>
                                    <button
                                        @click="newActivityType = 'call'"
                                        class="px-2 py-1 text-[11px] font-semibold rounded cursor-pointer transition-all"
                                        :class="newActivityType === 'call' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground'"
                                    >
                                        Ligação
                                    </button>
                                    <button
                                        @click="newActivityType = 'meeting'"
                                        class="px-2 py-1 text-[11px] font-semibold rounded cursor-pointer transition-all"
                                        :class="newActivityType === 'meeting' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground'"
                                    >
                                        Reunião
                                    </button>
                                </div>
                            </div>

                            <textarea
                                v-model="newActivityText"
                                rows="2"
                                placeholder="Digite um resumo da conversa, decisão ou tarefa..."
                                class="w-full rounded-md border border-border bg-background p-2.5 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                            ></textarea>

                            <div class="flex justify-end">
                                <Button size="sm" class="h-8 text-xs font-semibold cursor-pointer" @click="addActivity">
                                    Salvar Atividade
                                </Button>
                            </div>
                        </div>

                        <!-- Timeline de Atividades -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold text-muted-foreground uppercase tracking-wider">
                                Histórico de Interações
                            </h4>

                            <div v-if="!deal.activities || deal.activities.length === 0" class="py-8 text-center text-xs text-muted-foreground">
                                Nenhuma atividade registrada nesta oportunidade.
                            </div>

                            <div v-else class="relative space-y-4 before:absolute before:left-3 before:top-2 before:bottom-2 before:w-0.5 before:bg-border">
                                <div
                                    v-for="act in deal.activities"
                                    :key="act.id"
                                    class="relative flex gap-3 pl-8"
                                >
                                    <!-- Ícone do tipo -->
                                    <div class="absolute left-0 top-0 flex h-6 w-6 items-center justify-center rounded-full border border-border bg-background text-primary shadow-xs">
                                        <PhoneCall v-if="act.type === 'call'" class="h-3 w-3" />
                                        <CalendarCheck v-else-if="act.type === 'meeting'" class="h-3 w-3" />
                                        <FileText v-else class="h-3 w-3" />
                                    </div>

                                    <div class="flex-1 rounded-lg border border-border bg-card p-3 space-y-1 shadow-xs">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-semibold text-foreground">{{ act.user_name }}</span>
                                            <span class="text-[10px] text-muted-foreground flex items-center gap-1">
                                                <Clock class="h-3 w-3" /> {{ new Date(act.created_at).toLocaleString("pt-BR") }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-foreground/90 leading-relaxed">
                                            {{ act.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ABA 2: Itens da Proposta (Produtos e Serviços) -->
                    <div v-else-if="activeTab === 'items'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-muted-foreground uppercase tracking-wider">
                                Composição do Orçamento
                            </h4>
                            <Button size="sm" class="h-8 text-xs font-semibold cursor-pointer" @click="showAddItem = true">
                                <Plus class="mr-1 h-3.5 w-3.5" /> Adicionar Item
                            </Button>
                        </div>

                        <!-- Formulário Inline de Inclusão de Item -->
                        <div v-if="showAddItem" class="rounded-xl border border-primary/30 bg-primary/5 p-4 space-y-3">
                            <h5 class="text-xs font-bold text-foreground">Novo Item Comercial</h5>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="space-y-1">
                                    <Label class="text-[10px] font-semibold text-muted-foreground">Tipo</Label>
                                    <Select
                                        :model-value="newItemType"
                                        @update:model-value="(val: any) => newItemType = val"
                                    >
                                        <SelectTrigger class="h-8 bg-background text-xs">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="service">Serviço</SelectItem>
                                            <SelectItem value="product">Produto</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="col-span-2 space-y-1">
                                    <Label class="text-[10px] font-semibold text-muted-foreground">Descrição / Nome *</Label>
                                    <Input v-model="newItemName" placeholder="Ex: Licença PNET ou Treinamento" class="h-8 text-xs" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <Label class="text-[10px] font-semibold text-muted-foreground">Valor Unitário (R$) *</Label>
                                    <Input v-model="newItemPrice" type="number" placeholder="0,00" class="h-8 text-xs" />
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-[10px] font-semibold text-muted-foreground">Quantidade</Label>
                                    <Input v-model="newItemQty" type="number" min="1" class="h-8 text-xs" />
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 pt-1">
                                <Button size="sm" variant="ghost" class="h-7 text-xs" @click="showAddItem = false">Cancelar</Button>
                                <Button size="sm" class="h-7 text-xs font-semibold" @click="addItem">Adicionar ao Orçamento</Button>
                            </div>
                        </div>

                        <!-- Tabela de Itens -->
                        <div v-if="!deal.items || deal.items.length === 0" class="py-8 text-center text-xs text-muted-foreground rounded-lg border border-border">
                            Nenhum produto ou serviço adicionado a esta oportunidade.
                        </div>

                        <div v-else class="overflow-hidden rounded-lg border border-border">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-muted/60 text-muted-foreground font-semibold border-b border-border">
                                        <th class="p-3">Item</th>
                                        <th class="p-3 text-center">Tipo</th>
                                        <th class="p-3 text-center">Qtd</th>
                                        <th class="p-3 text-right">Unitário</th>
                                        <th class="p-3 text-right">Total</th>
                                        <th class="p-3 text-center w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr v-for="item in deal.items" :key="item.id" class="hover:bg-muted/30">
                                        <td class="p-3 font-medium text-foreground">{{ item.name }}</td>
                                        <td class="p-3 text-center">
                                            <span v-if="item.item_type === 'product'" class="rounded bg-blue-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-blue-500 flex items-center justify-center gap-1 w-fit mx-auto">
                                                <Package class="h-3 w-3" /> Produto
                                            </span>
                                            <span v-else class="rounded bg-purple-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-purple-500 flex items-center justify-center gap-1 w-fit mx-auto">
                                                <Wrench class="h-3 w-3" /> Serviço
                                            </span>
                                        </td>
                                        <td class="p-3 text-center font-mono">{{ item.quantity }}</td>
                                        <td class="p-3 text-right font-mono">{{ formatMoney(item.unit_price) }}</td>
                                        <td class="p-3 text-right font-bold text-foreground font-mono">{{ formatMoney(item.total_price) }}</td>
                                        <td class="p-3 text-center">
                                            <button @click="removeItem(item.id)" class="text-muted-foreground hover:text-rose-500 cursor-pointer">
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ABA 3: Histórico -->
                    <div v-else-if="activeTab === 'history'" class="space-y-3">
                        <h4 class="text-xs font-bold text-muted-foreground uppercase tracking-wider">
                            Auditoria de Mudanças
                        </h4>
                        <div class="rounded-lg border border-border p-4 text-xs space-y-3 text-muted-foreground">
                            <div class="flex items-center justify-between border-b border-border pb-2">
                                <span>Criado em</span>
                                <span class="font-mono text-foreground">{{ new Date(deal.created_at).toLocaleString("pt-BR") }}</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-border pb-2">
                                <span>Vendedor Responsável</span>
                                <span class="font-semibold text-foreground">{{ deal.user_name }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Estágio Atual</span>
                                <span class="font-semibold text-primary">{{ currentStage?.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
