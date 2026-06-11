<script setup lang="ts">
import { ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { ChevronLeft, FileText, Check, Landmark, User, DollarSign, CalendarDays } from "lucide-vue-next";
import { route } from "ziggy-js";
import axios from "axios";
import { toast } from "vue-sonner";
import { usePermission } from "@/composables/usePermission";
import { AccountPayable } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    accountPayable: AccountPayable;
}>();

const { permissions } = usePermission();

const showPayDialog = ref(false);
const installmentToPay = ref<number | string | null>(null);

// Format helpers
function formatMoney(cents: number | string | undefined | null) {
    if (cents === undefined || cents === null) return "0,00";
    const value = typeof cents === "string" ? parseInt(cents) : cents;
    return (value / 100).toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function formatDate(dateStr: string | null | undefined) {
    if (!dateStr) return "-";
    const parts = dateStr.split("T")[0].split("-");
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
}

function getStatusBadge(status: string) {
    switch (status) {
        case "paid":
            return { label: "Pago", class: "bg-emerald-50 text-emerald-700 border-emerald-200" };
        case "overdue":
            return { label: "Vencido", class: "bg-rose-50 text-rose-700 border-rose-200" };
        case "open":
        default:
            return { label: "Em Aberto", class: "bg-amber-50 text-amber-700 border-amber-200" };
    }
}

function getPaymentMethodLabel(val: string | number | undefined | null) {
    if (val === undefined || val === null) return "-";
    const s = String(val).toLowerCase();
    if (s === "1" || s.includes("money") || s.includes("dinheiro")) return "Dinheiro";
    if (s === "2" || s.includes("pix")) return "Pix";
    if (s === "3" || s.includes("ticket") || s.includes("boleto")) return "Boleto";
    if (s === "4" || s.includes("credit") || s.includes("cartao") || s.includes("cartão")) return "Cartão de Crédito";
    return val;
}

function getPaymentConditionLabel(val: string | undefined | null) {
    if (!val) return "-";
    if (val === "a-vista") return "À Vista";
    return `${val}x`;
}

// Action execution
function openPay(installmentId: number | string) {
    installmentToPay.value = installmentId;
    showPayDialog.value = true;
}

async function executePay() {
    if (installmentToPay.value) {
        try {
            const res = await axios.patch(route("tenant.finance.accounts-payable.installments.update"), {
                id: installmentToPay.value,
            });
            if (res.data?.success) {
                toast.success("Parcela liquidada com sucesso!");
                showPayDialog.value = false;
                installmentToPay.value = null;
                router.reload();
            } else {
                toast.error("Erro ao liquidar parcela.");
            }
        } catch (err: any) {
            toast.error(err.response?.data?.message || "Erro ao liquidar parcela.");
        }
    }
}
</script>

<template>
    <Head title="Detalhes do Lançamento" />

    <div class="mb-6 flex flex-col gap-4 border-b border-border pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Detalhes do Lançamento
            </h2>
            <p class="text-sm text-muted-foreground">Lançamento Nº {{ String(props.accountPayable.id).padStart(5, '0') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <Button variant="outline" class="cursor-pointer" as-child>
                <Link :href="route('tenant.finance.accounts-payable.list')">
                    <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
                </Link>
            </Button>
            <Button
                v-if="permissions.includes('finance.accounts_payable.edit')"
                class="cursor-pointer"
                as-child
            >
                <Link :href="route('tenant.finance.accounts-payable.edit', props.accountPayable.id)">
                    Editar Lançamento
                </Link>
            </Button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Col 1 & 2: Details Grid -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Card de Dados Gerais -->
            <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-card-foreground border-b border-border pb-3 mb-4 flex items-center gap-2">
                    <FileText class="h-5 w-5 text-muted-foreground" />
                    Informações Gerais
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6">
                    <div>
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">Descrição</span>
                        <span class="text-sm font-medium text-foreground block mt-1">
                            {{ props.accountPayable.description }}
                        </span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">Fornecedor</span>
                        <span class="text-sm font-medium text-foreground block mt-1 flex items-center gap-1.5">
                            <User class="h-4 w-4 text-muted-foreground/80" />
                            {{ props.accountPayable.financial_contact?.name_corporatereason || props.accountPayable.financial_contact?.fantasy_name || "-" }}
                        </span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">Conta Bancária de Origem</span>
                        <span class="text-sm font-medium text-foreground block mt-1 flex items-center gap-1.5">
                            <Landmark class="h-4 w-4 text-muted-foreground/80" />
                            {{ props.accountPayable.bank_account?.name || "-" }}
                        </span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">Centro de Custo</span>
                        <span class="text-sm font-medium text-foreground block mt-1">
                            {{ props.accountPayable.cost?.type || "Não Informado" }}
                        </span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">Categoria Financeira</span>
                        <span class="text-sm font-medium text-foreground block mt-1">
                            {{ props.accountPayable.financial_category?.name || "-" }}
                        </span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">Subcategoria Financeira</span>
                        <span class="text-sm font-medium text-foreground block mt-1">
                            {{ props.accountPayable.financial_subcategory?.name || "-" }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card de Detalhamento das Parcelas -->
            <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-border bg-muted/10">
                    <h3 class="text-lg font-semibold text-card-foreground flex items-center gap-2">
                        <CalendarDays class="h-5 w-5 text-muted-foreground" />
                        Cronograma de Pagamento
                    </h3>
                </div>
                
                <Table>
                    <TableHeader class="bg-muted/30">
                        <TableRow>
                            <TableHead class="w-24">Parcela</TableHead>
                            <TableHead>Vencimento</TableHead>
                            <TableHead>Pagamento</TableHead>
                            <TableHead class="text-right">Valor</TableHead>
                            <TableHead class="w-32 text-center">Situação</TableHead>
                            <TableHead class="w-24 text-center">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="props.accountPayable.installments && props.accountPayable.installments.length > 0">
                            <TableRow v-for="(inst, idx) in props.accountPayable.installments" :key="idx" class="hover:bg-muted/10">
                                <TableCell class="font-semibold text-foreground">
                                    {{ inst.installment_number }} / {{ props.accountPayable.total_installments }}
                                </TableCell>
                                <TableCell>{{ formatDate(inst.due_date) }}</TableCell>
                                <TableCell>{{ formatDate(inst.payment_date) }}</TableCell>
                                <TableCell class="text-right font-bold text-foreground">
                                    R$ {{ formatMoney(inst.value) }}
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="outline" :class="['px-2.5 py-0.5 text-xs font-semibold rounded-full border', getStatusBadge(inst.status).class]">
                                        {{ getStatusBadge(inst.status).label }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Button
                                        v-if="permissions.includes('finance.accounts_payable.edit') && inst.status !== 'paid'"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 cursor-pointer rounded-full"
                                        title="Liquidar Parcela"
                                        @click="openPay(inst.id)"
                                    >
                                        <Check class="h-4 w-4" />
                                    </Button>
                                    <span v-else-if="inst.status === 'paid'" class="text-xs text-emerald-600 font-semibold flex items-center justify-center gap-1">
                                        Liquidado
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground font-semibold flex items-center justify-center gap-1">
                                        -
                                    </span>
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else>
                            <TableRow>
                                <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                                    Nenhuma parcela encontrada para este lançamento.
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Col 3: Sidebar Summary -->
        <div class="space-y-6">
            <!-- Card de Condições de Venda -->
            <div class="rounded-xl border border-border bg-card p-6 shadow-sm space-y-6">
                <h3 class="text-lg font-semibold text-card-foreground border-b border-border pb-3 flex items-center gap-2">
                    <DollarSign class="h-5 w-5 text-muted-foreground" />
                    Resumo do Lançamento
                </h3>

                <div class="space-y-4">
                    <div>
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">Valor Total do Contrato</span>
                        <span class="text-3xl font-extrabold text-primary block mt-1">
                            R$ {{ formatMoney(props.accountPayable.total) }}
                        </span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">Condição de Pagamento</span>
                        <span class="text-sm font-semibold text-foreground block mt-1">
                            {{ getPaymentConditionLabel(props.accountPayable.payment_condition) }}
                        </span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">Forma de Pagamento</span>
                        <span class="text-sm font-semibold text-foreground block mt-1">
                            {{ getPaymentMethodLabel(props.accountPayable.payment_method) }}
                        </span>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">Nº Total de Parcelas</span>
                        <span class="text-sm font-semibold text-foreground block mt-1">
                            {{ props.accountPayable.total_installments || 1 }} parcelas(s)
                        </span>
                    </div>

                    <div v-if="props.accountPayable.receipt">
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">Comprovante / Anexo</span>
                        <a
                            :href="props.accountPayable.receipt"
                            target="_blank"
                            class="text-sm text-blue-600 hover:underline hover:text-blue-700 font-semibold block mt-1 break-all"
                        >
                            {{ props.accountPayable.receipt }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card de Observações -->
            <div v-if="props.accountPayable.observations" class="rounded-xl border border-border bg-card p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-card-foreground border-b border-border pb-2 mb-3">
                    Observações Internas
                </h3>
                <p class="text-sm text-muted-foreground leading-relaxed whitespace-pre-wrap">
                    {{ props.accountPayable.observations }}
                </p>
            </div>
        </div>
    </div>

    <!-- Confirm payment/liquidation alert dialog -->
    <AlertDialog :open="showPayDialog" @update:open="showPayDialog = $event">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Liquidar Parcela</AlertDialogTitle>
                <AlertDialogDescription>
                    Deseja marcar esta parcela como **Paga**? Esta ação atualizará o saldo da conta bancária de origem.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="showPayDialog = false">Cancelar</AlertDialogCancel>
                <AlertDialogAction class="bg-emerald-600 text-white hover:bg-emerald-700 cursor-pointer" @click="executePay">
                    Confirmar Pagamento
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

<style scoped></style>
