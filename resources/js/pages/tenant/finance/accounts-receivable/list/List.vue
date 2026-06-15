<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
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
import {
    Plus,
    Search,
    ChevronLeft,
    ChevronRight,
    Calendar as CalendarIcon,
    HelpCircle,
} from "lucide-vue-next";
import { route } from "ziggy-js";
import axios from "axios";
import { toast } from "vue-sonner";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import { Calendar } from "@/components/ui/calendar";
import { parseDate, today, getLocalTimeZone } from "@internationalized/date";
import { usePermission } from "@/composables/usePermission";
import { AccountReceivable, BankAccount, FinanceCategory } from "@/types";
import ActionDropdown from "./ActionDropdown.vue";

defineOptions({ layout: TenantLayout });

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    accountsReceivable: {
        data: AccountReceivable[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
        links: PaginationLink[];
    };
    totalPeriod: number | string;
    totalPaid: number | string;
    totalDueToday: number | string;
    totalToDue: number | string;
    totalOverdue: number | string;
    period: string; // Y-m format, e.g. "2026-06"
    perPage?: string | number;
    start?: string;
    end?: string;
    status?: string;
    categoryId?: string | number;
    search?: string;
    financialCategories: FinanceCategory[];
    searchedCategory?: { name: string } | null;
    bankAccounts: BankAccount[];
    bankAccount?: BankAccount | null;
}>();

const { permissions } = usePermission();

const searchQuery = ref(props.search || "");

const createUrlWithFilters = computed(() => {
    return (
        route("tenant.finance.accounts-receivable.create") +
        (typeof window !== "undefined" ? window.location.search : "")
    );
});
const showDeleteDialog = ref(false);
const itemToDelete = ref<number | string | null>(null);

const showPayDialog = ref(false);
const itemToPay = ref<number | string | null>(null);

const filterMode = ref<"monthly" | "custom">(
    props.start || props.end ? "custom" : "monthly",
);
const customStart = ref(props.start || "");
const customEnd = ref(props.end || "");

const startCalendarDate = computed({
    get: () => (customStart.value ? parseDate(customStart.value) : undefined),
    set: (val) => {
        customStart.value = val ? val.toString() : "";
    },
});

const endCalendarDate = computed({
    get: () => (customEnd.value ? parseDate(customEnd.value) : undefined),
    set: (val) => {
        customEnd.value = val ? val.toString() : "";
    },
});

const startCalendarPlaceholder = ref<any>(
    customStart.value
        ? parseDate(customStart.value)
        : today(getLocalTimeZone()),
);

const endCalendarPlaceholder = ref<any>(
    customEnd.value ? parseDate(customEnd.value) : today(getLocalTimeZone()),
);

watch(
    () => props.start,
    (newVal) => {
        customStart.value = newVal || "";
        startCalendarPlaceholder.value = newVal
            ? parseDate(newVal)
            : today(getLocalTimeZone());
    },
);

watch(
    () => props.end,
    (newVal) => {
        customEnd.value = newVal || "";
        endCalendarPlaceholder.value = newVal
            ? parseDate(newVal)
            : today(getLocalTimeZone());
    },
);

watch(
    () => props.search,
    (newVal) => {
        searchQuery.value = newVal || "";
    },
);

function formatDisplayDate(dateStr: string) {
    if (!dateStr) return "";
    const parts = dateStr.split("-");
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
}

// Month lists for Portuguese
const monthsList = [
    { value: "01", name: "Janeiro" },
    { value: "02", name: "Fevereiro" },
    { value: "03", name: "Março" },
    { value: "04", name: "Abril" },
    { value: "05", name: "Maio" },
    { value: "06", name: "Junho" },
    { value: "07", name: "Julho" },
    { value: "08", name: "Agosto" },
    { value: "09", name: "Setembro" },
    { value: "10", name: "Outubro" },
    { value: "11", name: "Novembro" },
    { value: "12", name: "Dezembro" },
];

const currentYear = computed(() => {
    return props.period
        ? props.period.split("-")[0]
        : new Date().getFullYear().toString();
});

const currentMonthObj = computed(() => {
    const m = props.period
        ? props.period.split("-")[1]
        : String(new Date().getMonth() + 1).padStart(2, "0");
    return monthsList.find((item) => item.value === m) || monthsList[5];
});

const formattedPeriodLabel = computed(() => {
    return `${currentMonthObj.value.name} de ${currentYear.value}`;
});

interface InstallmentRow {
    id: string | number;
    installment_id: string | number | null;
    bank_account_name: string;
    due_date: string;
    payment_date: string | null;
    installment_number: number;
    total_installments: number;
    description: string;
    value: number;
    total: number;
    status: string;
    raw_ar: AccountReceivable;
    raw_inst: any;
}

// Flat-mapping accountsReceivable.data into individual installments
const installmentsList = computed<InstallmentRow[]>(() => {
    const list: InstallmentRow[] = [];
    if (!props.accountsReceivable?.data) return list;

    props.accountsReceivable.data.forEach((ar) => {
        const insts = ar.installments || [];
        if (insts && insts.length > 0) {
            insts.forEach((inst) => {
                list.push({
                    id: ar.id,
                    installment_id: inst.id,
                    bank_account_name:
                        ar.bank_account?.name || props.bankAccount?.name || "-",
                    due_date: inst.due_date,
                    payment_date: inst.payment_date || null,
                    installment_number: inst.installment_number,
                    total_installments: ar.total_installments || 1,
                    description: ar.description,
                    value: inst.value,
                    total: ar.total,
                    status: inst.status,
                    raw_ar: ar,
                    raw_inst: inst,
                });
            });
        } else {
            // Fallback row if installments relation is not loaded
            list.push({
                id: ar.id,
                installment_id: null,
                bank_account_name:
                    ar.bank_account?.name || props.bankAccount?.name || "-",
                due_date: ar.due_date || ar.created_at || "",
                payment_date: null,
                installment_number: 1,
                total_installments: ar.total_installments || 1,
                description: ar.description,
                value: ar.total,
                total: ar.total,
                status: ar.status || "open",
                raw_ar: ar,
                raw_inst: null,
            });
        }
    });
    return list;
});

function reload(extraParams = {}) {
    const params: any = {
        periodo: props.period,
        quantidade: props.perPage || 10,
        search: searchQuery.value || undefined,
        conta_id: props.bankAccount?.id || undefined,
        categoria_id: props.categoryId || undefined,
        status: props.status || undefined,
        ...extraParams,
    };

    if (filterMode.value === "custom") {
        params.inicio = customStart.value || undefined;
        params.fim = customEnd.value || undefined;
        params.periodo = undefined; // clear month period
    } else {
        params.inicio = undefined;
        params.fim = undefined;
    }

    router.get(route("tenant.finance.accounts-receivable.list"), params, {
        preserveState: true,
        preserveScroll: true,
    });
}

function onSearch() {
    reload();
}

function navigateMonth(direction: "prev" | "next") {
    let year = parseInt(currentYear.value);
    let month = parseInt(currentMonthObj.value.value);

    if (direction === "prev") {
        month--;
        if (month < 1) {
            month = 12;
            year--;
        }
    } else {
        month++;
        if (month > 12) {
            month = 1;
            year++;
        }
    }

    const newPeriod = `${year}-${String(month).padStart(2, "0")}`;
    reload({ periodo: newPeriod, inicio: null, fim: null });
}

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
            return {
                label: "Recebido",
                class: "bg-emerald-50 text-emerald-700 border-emerald-200",
            };
        case "overdue":
            return {
                label: "Vencido",
                class: "bg-rose-50 text-rose-700 border-rose-200",
            };
        case "open":
        default:
            return {
                label: "Em Aberto",
                class: "bg-amber-50 text-amber-700 border-amber-200",
            };
    }
}

// Action execution
function openDelete(id: number | string) {
    itemToDelete.value = id;
    showDeleteDialog.value = true;
}

function executeDelete() {
    if (itemToDelete.value) {
        router.delete(
            route(
                "tenant.finance.accounts-receivable.destroy",
                itemToDelete.value,
            ),
            {
                preserveScroll: true,
                onSuccess: () => {
                    showDeleteDialog.value = false;
                    itemToDelete.value = null;
                },
            },
        );
    }
}

function openPay(installmentId: number | string) {
    itemToPay.value = installmentId;
    showPayDialog.value = true;
}

async function executePay() {
    if (itemToPay.value) {
        try {
            const res = await axios.patch(
                route("tenant.finance.accounts-receivable.installments.update"),
                {
                    id: itemToPay.value,
                },
            );
            if (res.data?.success) {
                toast.success("Parcela recebida com sucesso!");
                showPayDialog.value = false;
                itemToPay.value = null;
                router.reload();
            } else {
                toast.error("Erro ao receber parcela.");
            }
        } catch (err: any) {
            toast.error(
                err.response?.data?.message || "Erro de conexão ao liquidar.",
            );
        }
    }
}
</script>

<template>
    <Head title="Contas a Receber" />
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 border-b border-border pb-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Contas a Receber
                </h2>
                <p class="text-sm text-muted-foreground">
                    Gerencie seus lançamentos e recebimentos financeiros.
                </p>
            </div>
            <Button
                v-if="
                    permissions.includes('finance.accounts_receivable.create')
                "
                class="cursor-pointer font-semibold"
                variant="outline"
                as-child
            >
                <Link :href="createUrlWithFilters">
                    <Plus class="mr-2 h-4 w-4" /> Novo Lançamento
                </Link>
            </Button>
        </div>

        <!-- Filters section -->
        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div class="flex flex-col gap-6">
                <!-- Row 1: Filter Type and Period Selection -->
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="flex flex-wrap items-end gap-4">
                        <!-- Mode toggle -->
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >Modo do Filtro</label
                            >
                            <div
                                class="flex rounded-md border border-border bg-muted/30 p-1"
                            >
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 cursor-pointer rounded-sm text-xs font-medium"
                                    :class="{
                                        'border border-border/50 bg-white text-foreground shadow-sm':
                                            filterMode === 'monthly',
                                    }"
                                    @click="
                                        filterMode = 'monthly';
                                        reload();
                                    "
                                >
                                    Filtro Mensal
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 cursor-pointer rounded-sm text-xs font-medium"
                                    :class="{
                                        'border border-border/50 bg-white text-foreground shadow-sm':
                                            filterMode === 'custom',
                                    }"
                                    @click="filterMode = 'custom'"
                                >
                                    Período Personalizado
                                </Button>
                            </div>
                        </div>

                        <!-- Monthly mode controls -->
                        <div
                            v-if="filterMode === 'monthly'"
                            class="space-y-1.5"
                        >
                            <label
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >Período</label
                            >
                            <div
                                class="flex items-center gap-1 rounded-lg border border-border bg-background p-1 shadow-xs"
                            >
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-9 w-9 cursor-pointer"
                                    @click="navigateMonth('prev')"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </Button>

                                <div class="flex items-center gap-2 px-3">
                                    <span
                                        class="min-w-[120px] text-center text-sm font-semibold text-foreground"
                                    >
                                        {{ formattedPeriodLabel }}
                                    </span>
                                </div>

                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-9 w-9 cursor-pointer"
                                    @click="navigateMonth('next')"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Custom mode inputs -->
                        <div v-else class="flex flex-wrap items-end gap-3">
                            <div class="flex flex-col space-y-1.5">
                                <label
                                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    >Data Inicial</label
                                >
                                <Popover>
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            class="h-10 w-[180px] cursor-pointer justify-start border border-border bg-background text-left text-sm font-normal"
                                            :class="{
                                                'text-muted-foreground':
                                                    !customStart,
                                            }"
                                        >
                                            <CalendarIcon
                                                class="mr-2 h-4 w-4 shrink-0 text-muted-foreground"
                                            />
                                            {{
                                                customStart
                                                    ? formatDisplayDate(
                                                          customStart,
                                                      )
                                                    : "Selecione..."
                                            }}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        class="w-auto p-0"
                                        align="start"
                                    >
                                        <Calendar
                                            v-model="startCalendarDate"
                                            v-model:placeholder="
                                                startCalendarPlaceholder
                                            "
                                            locale="pt-BR"
                                            initial-focus
                                        />
                                    </PopoverContent>
                                </Popover>
                            </div>
                            <div class="flex flex-col space-y-1.5">
                                <label
                                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    >Data Final</label
                                >
                                <Popover>
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            class="h-10 w-[180px] cursor-pointer justify-start border border-border bg-background text-left text-sm font-normal"
                                            :class="{
                                                'text-muted-foreground':
                                                    !customEnd,
                                            }"
                                        >
                                            <CalendarIcon
                                                class="mr-2 h-4 w-4 shrink-0 text-muted-foreground"
                                            />
                                            {{
                                                customEnd
                                                    ? formatDisplayDate(
                                                          customEnd,
                                                      )
                                                    : "Selecione..."
                                            }}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        class="w-auto p-0"
                                        align="start"
                                    >
                                        <Calendar
                                            v-model="endCalendarDate"
                                            v-model:placeholder="
                                                endCalendarPlaceholder
                                            "
                                            locale="pt-BR"
                                            initial-focus
                                        />
                                    </PopoverContent>
                                </Popover>
                            </div>
                            <Button
                                @click="reload()"
                                class="h-10 cursor-pointer"
                            >
                                Filtrar
                            </Button>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="w-full space-y-1.5 lg:max-w-md">
                        <label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Pesquisar no período selecionado</label
                        >
                        <div class="flex gap-2">
                            <Input
                                placeholder="Pesquisar..."
                                v-model="searchQuery"
                                class="h-10 text-sm"
                                @keyup.enter="onSearch"
                            />
                            <Button
                                @click="onSearch"
                                size="icon"
                                class="h-10 w-10 shrink-0 cursor-pointer"
                            >
                                <Search class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Secondary selectors (Bank accounts, Categories, Status) -->
                <div
                    class="grid grid-cols-1 gap-4 border-t border-border/60 pt-4 md:grid-cols-4"
                >
                    <div class="space-y-1.5">
                        <label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Conta Bancária</label
                        >
                        <Select
                            :model-value="
                                props.bankAccount?.id
                                    ? String(props.bankAccount.id)
                                    : 'all'
                            "
                            @update:model-value="
                                (val) =>
                                    reload({
                                        conta_id: val === 'all' ? null : val,
                                    })
                            "
                        >
                            <SelectTrigger
                                class="h-10 border border-border bg-background text-sm"
                            >
                                <SelectValue
                                    placeholder="Selecione a conta..."
                                />
                            </SelectTrigger>
                            <SelectContent side="bottom">
                                <SelectItem value="all"
                                    >Todas as Contas</SelectItem
                                >
                                <SelectItem
                                    v-for="acc in props.bankAccounts"
                                    :key="acc.id"
                                    :value="String(acc.id)"
                                >
                                    {{ acc.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            class="text-[10px] text-muted-foreground"
                            v-if="props.bankAccount"
                        >
                            Saldo: R$
                            {{ formatMoney(props.bankAccount.current_balance) }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Categoria Financeira</label
                        >
                        <Select
                            :model-value="
                                props.categoryId
                                    ? String(props.categoryId)
                                    : 'all'
                            "
                            @update:model-value="
                                (val) =>
                                    reload({
                                        categoria_id:
                                            val === 'all' ? null : val,
                                    })
                            "
                        >
                            <SelectTrigger
                                class="h-10 border border-border bg-background text-sm"
                            >
                                <SelectValue
                                    placeholder="Selecione a categoria..."
                                />
                            </SelectTrigger>
                            <SelectContent side="bottom">
                                <SelectItem value="all"
                                    >Todas as Categorias</SelectItem
                                >
                                <SelectItem
                                    v-for="cat in props.financialCategories"
                                    :key="cat.id"
                                    :value="String(cat.id)"
                                >
                                    {{ cat.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Situação</label
                        >
                        <Select
                            :model-value="props.status || 'all'"
                            @update:model-value="
                                (val) =>
                                    reload({
                                        status: val === 'all' ? null : val,
                                    })
                            "
                        >
                            <SelectTrigger
                                class="h-10 border border-border bg-background text-sm"
                            >
                                <SelectValue
                                    placeholder="Selecione a situação..."
                                />
                            </SelectTrigger>
                            <SelectContent side="bottom">
                                <SelectItem value="all"
                                    >Todas as Situações</SelectItem
                                >
                                <SelectItem value="a-vencer"
                                    >A Vencer</SelectItem
                                >
                                <SelectItem value="vencidos"
                                    >Vencidos</SelectItem
                                >
                                <SelectItem value="vencem-hoje"
                                    >Vencem Hoje</SelectItem
                                >
                                <SelectItem value="pago">Recebido</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Registros por página</label
                        >
                        <Select
                            :model-value="String(props.perPage || 10)"
                            @update:model-value="
                                (val) => reload({ quantidade: val })
                            "
                        >
                            <SelectTrigger
                                class="h-10 border border-border bg-background text-sm"
                            >
                                <SelectValue placeholder="Quantidade..." />
                            </SelectTrigger>
                            <SelectContent side="bottom">
                                <SelectItem
                                    v-for="size in [
                                        '10',
                                        '20',
                                        '30',
                                        '50',
                                        '100',
                                    ]"
                                    :key="size"
                                    :value="size"
                                >
                                    Mostrar {{ size }} registros
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metrics cards (Dashboard totals) -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Overdue card -->
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-xs transition hover:shadow-sm"
            >
                <div class="space-y-2">
                    <p
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Vencidos (R$)
                    </p>
                    <p class="text-3xl font-extrabold text-rose-600">
                        {{ formatMoney(props.totalOverdue) }}
                    </p>
                </div>
            </div>

            <!-- Due Today card -->
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-xs transition hover:shadow-sm"
            >
                <div class="space-y-2">
                    <p
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Vencem hoje (R$)
                    </p>
                    <p class="text-3xl font-extrabold text-rose-600">
                        {{ formatMoney(props.totalDueToday) }}
                    </p>
                </div>
            </div>

            <!-- To Due card -->
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-xs transition hover:shadow-sm"
            >
                <div class="flex items-center justify-between space-y-2">
                    <div>
                        <p
                            class="flex items-center gap-1 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            A receber (R$)
                            <span
                                class="cursor-help text-muted-foreground/60"
                                title="Próximos 7 dias"
                            >
                                <HelpCircle class="h-3 w-3" />
                            </span>
                        </p>
                        <p class="text-3xl font-extrabold text-foreground">
                            {{ formatMoney(props.totalToDue) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Paid card -->
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-xs transition hover:shadow-sm"
            >
                <div class="space-y-2">
                    <p
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Recebidos (R$)
                    </p>
                    <p class="text-3xl font-extrabold text-emerald-600">
                        {{ formatMoney(props.totalPaid) }}
                    </p>
                </div>
            </div>

            <!-- Total period card -->
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-xs transition hover:shadow-sm"
            >
                <div class="space-y-2">
                    <p
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Total do período (R$)
                    </p>
                    <p class="text-3xl font-extrabold text-primary">
                        {{ formatMoney(props.totalPeriod) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Table view -->
        <div
            class="overflow-hidden rounded-xl border border-border bg-card shadow-sm"
        >
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader class="bg-muted/30">
                        <TableRow>
                            <TableHead class="w-16 font-semibold">Nº</TableHead>
                            <TableHead class="font-semibold"
                                >Conta Bancária</TableHead
                            >
                            <TableHead class="font-semibold"
                                >Vencimento</TableHead
                            >
                            <TableHead class="font-semibold"
                                >Recebimento</TableHead
                            >
                            <TableHead class="w-32 font-semibold"
                                >Parcelamento</TableHead
                            >
                            <TableHead class="min-w-[200px] font-semibold"
                                >Descrição</TableHead
                            >
                            <TableHead class="text-right font-semibold"
                                >Valor da parcela</TableHead
                            >
                            <TableHead class="text-right font-semibold"
                                >Total (R$)</TableHead
                            >
                            <TableHead class="w-32 text-center font-semibold"
                                >Situação</TableHead
                            >
                            <TableHead class="w-12 text-center font-semibold"
                                >Ação</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="installmentsList.length > 0">
                            <TableRow
                                v-for="(item, idx) in installmentsList"
                                :key="idx"
                                class="hover:bg-muted/10"
                            >
                                <TableCell
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ String(item.id).padStart(5, "0") }}
                                </TableCell>
                                <TableCell class="font-medium text-foreground">
                                    {{ item.bank_account_name }}
                                </TableCell>
                                <TableCell>{{
                                    formatDate(item.due_date)
                                }}</TableCell>
                                <TableCell>{{
                                    formatDate(item.payment_date)
                                }}</TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ item.installment_number }} /
                                    {{ item.total_installments }}
                                </TableCell>
                                <TableCell
                                    class="max-w-[300px] truncate text-muted-foreground"
                                    :title="item.description"
                                >
                                    {{ item.description }}
                                </TableCell>
                                <TableCell
                                    class="text-right font-semibold text-foreground"
                                >
                                    R$ {{ formatMoney(item.value) }}
                                </TableCell>
                                <TableCell
                                    class="text-right text-muted-foreground"
                                >
                                    R$ {{ formatMoney(item.total) }}
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge
                                        variant="outline"
                                        :class="[
                                            'rounded-full border px-2.5 py-1 text-xs font-semibold',
                                            getStatusBadge(item.status).class,
                                        ]"
                                    >
                                        {{ getStatusBadge(item.status).label }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-center">
                                    <div
                                        class="flex items-center justify-center"
                                    >
                                        <ActionDropdown
                                            :item="item"
                                            @pay="openPay"
                                            @delete="openDelete"
                                        />
                                    </div>
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else>
                            <TableRow>
                                <TableCell
                                    colspan="10"
                                    class="h-28 text-center text-muted-foreground"
                                >
                                    Nenhum lançamento de contas a receber
                                    encontrado para este período.
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>

            <!-- Server-side Pagination Links -->
            <div
                v-if="props.accountsReceivable?.last_page > 1"
                class="flex items-center justify-between border-t border-border bg-muted/10 px-6 py-4"
            >
                <div class="text-sm text-muted-foreground">
                    Mostrando {{ installmentsList.length }} lançamentos de
                    {{ props.accountsReceivable.total }} total
                </div>
                <div class="flex items-center gap-1.5">
                    <Button
                        v-for="(link, lIdx) in props.accountsReceivable.links"
                        :key="lIdx"
                        variant="outline"
                        size="sm"
                        :class="[
                            'h-8 cursor-pointer px-3 text-xs font-semibold',
                            {
                                'bg-primary font-bold text-primary-foreground hover:bg-primary/95 hover:text-primary-foreground':
                                    link.active,
                            },
                        ]"
                        :disabled="!link.url"
                        as-child
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            preserve-state
                            v-html="link.label"
                        />
                        <span v-else v-html="link.label" />
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm delete alert dialog -->
    <AlertDialog
        :open="showDeleteDialog"
        @update:open="showDeleteDialog = $event"
    >
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Você tem certeza absoluta?</AlertDialogTitle>
                <AlertDialogDescription>
                    Esta ação não pode ser desfeita. Isso excluirá
                    permanentemente o lançamento de contas a receber e todas as
                    suas parcelas associadas de nossos servidores.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="showDeleteDialog = false"
                    >Cancelar</AlertDialogCancel
                >
                <AlertDialogAction
                    class="cursor-pointer bg-destructive text-white hover:bg-destructive/90"
                    @click="executeDelete"
                >
                    Confirmar Exclusão
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>

    <!-- Confirm payment/liquidation alert dialog -->
    <AlertDialog :open="showPayDialog" @update:open="showPayDialog = $event">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Receber Parcela</AlertDialogTitle>
                <AlertDialogDescription>
                    Deseja marcar esta parcela como **Recebida**? Esta ação
                    atualizará o saldo da conta bancária de destino.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="showPayDialog = false"
                    >Cancelar</AlertDialogCancel
                >
                <AlertDialogAction
                    class="cursor-pointer bg-emerald-600 text-white hover:bg-emerald-700"
                    @click="executePay"
                >
                    Confirmar Recebimento
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

<style scoped></style>
