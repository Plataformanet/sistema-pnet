<script setup lang="ts">
import { ref, computed, watch } from "vue";
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import { Calendar } from "@/components/ui/calendar";
import { parseDate, today, getLocalTimeZone } from "@internationalized/date";
import { ChevronLeft, ChevronRight, HelpCircle, Calendar as CalendarIcon } from "lucide-vue-next";
import { route } from "ziggy-js";
import { BankAccount, FinanceCategory } from "@/types";

defineOptions({ layout: TenantLayout });

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    accounts: {
        data: any[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
        links: PaginationLink[];
    };
    totalPeriod: number | string;
    totalOpen: number | string;
    accountsResult: any;
    expenses: number | string;
    revenues: number | string;
    period: string; // Y-m format, e.g. "2026-06"
    perPage?: string | number;
    start?: string;
    end?: string;
    categoryId?: string | number;
    accountId?: string | number | null;
    financialCategories: FinanceCategory[];
    type: string;
    bankAccounts: BankAccount[];
    bankAccount?: BankAccount | null;
    totalBankAccounts: number;
    total: number;
}>();

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

const currentStatus = computed(() => {
    if (typeof window !== "undefined") {
        return (
            new URLSearchParams(window.location.search).get("status") || null
        );
    }
    return null;
});

const getInitialFilterMode = () => {
    if (props.start && props.end) {
        const isYearStart = props.start.endsWith("-01-01");
        const isYearEnd = props.end.endsWith("-12-31");
        if (isYearStart && isYearEnd && props.start.split("-")[0] === props.end.split("-")[0]) {
            return "yearly";
        }
        return "custom";
    }
    return "monthly";
};

const filterMode = ref<"monthly" | "yearly" | "custom">(getInitialFilterMode());

const customStart = ref(props.start || "");
const customEnd = ref(props.end || "");
const selectedYear = ref(props.start ? props.start.split("-")[0] : new Date().getFullYear().toString());

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

// Watchers for date properties to sync with props
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
        
        if (newVal) {
            selectedYear.value = newVal.split("-")[0];
        }
    },
);

// Prevent overlap watchers
watch(customStart, (newStartVal) => {
    if (newStartVal && customEnd.value) {
        const start = parseDate(newStartVal);
        const end = parseDate(customEnd.value);
        if (start.compare(end) > 0) {
            customEnd.value = newStartVal;
        }
    }
});

watch(customEnd, (newEndVal) => {
    if (newEndVal && customStart.value) {
        const start = parseDate(customStart.value);
        const end = parseDate(newEndVal);
        if (end.compare(start) < 0) {
            customStart.value = newEndVal;
        }
    }
});

interface InstallmentRow {
    id: string | number;
    installment_id: string | number | null;
    bank_account_name: string;
    due_date: string;
    payment_date: string | null;
    category_name: string;
    type: "Despesa" | "Receita";
    value: number; // in cents, signed (+ for Receita, - for Despesa)
    status: string;
    description: string;
}

// Flat-mapping accounts into individual installments with proper sign
const installmentsList = computed<InstallmentRow[]>(() => {
    const list: InstallmentRow[] = [];
    if (!props.accounts?.data) return list;

    props.accounts.data.forEach((acc: any) => {
        const isPayable = acc.type === "accounts_payable";
        const typeLabel = isPayable ? "Despesa" : "Receita";

        const insts = acc.installments || [];
        if (insts && insts.length > 0) {
            insts.forEach((inst: any) => {
                const signedValue = isPayable ? -inst.value : inst.value;
                list.push({
                    id: acc.id,
                    installment_id: inst.id,
                    bank_account_name:
                        acc.bank_account?.name ||
                        props.bankAccount?.name ||
                        "-",
                    due_date: inst.due_date,
                    payment_date: inst.payment_date || null,
                    category_name: acc.financial_category?.name || "-",
                    type: typeLabel,
                    value: signedValue,
                    status: inst.status,
                    description: acc.description || "",
                });
            });
        } else {
            const signedValue = isPayable ? -acc.total : acc.total;
            list.push({
                id: acc.id,
                installment_id: null,
                bank_account_name:
                    acc.bank_account?.name || props.bankAccount?.name || "-",
                due_date: acc.due_date || acc.created_at || "",
                payment_date: null,
                category_name: acc.financial_category?.name || "-",
                type: typeLabel,
                value: signedValue,
                status: acc.status || "open",
                description: acc.description || "",
            });
        }
    });

    // Sort by due_date desc
    return list.sort(
        (a, b) =>
            new Date(b.due_date).getTime() - new Date(a.due_date).getTime(),
    );
});

function reload(extraParams = {}) {
    const params: any = {
        period: props.period,
        quantity: props.perPage || 10,
        account_id: props.accountId ?? undefined,
        category_id: props.categoryId || undefined,
        status: currentStatus.value || undefined,
        start: props.start || undefined,
        end: props.end || undefined,
        ...extraParams,
    };

    if (filterMode.value === "custom") {
        params.start = customStart.value || undefined;
        params.end = customEnd.value || undefined;
        params.period = undefined;
    } else if (filterMode.value === "yearly") {
        params.period = undefined;
    } else {
        params.start = undefined;
        params.end = undefined;
    }

    router.get(route("tenant.finance.cash-flow.index"), params, {
        preserveState: true,
        preserveScroll: true,
    });
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
    reload({ period: newPeriod, start: null, end: null });
}

function applyYearlyFilter() {
    const start = `${selectedYear.value}-01-01`;
    const end = `${selectedYear.value}-12-31`;
    reload({ start, end, period: null });
}

function navigateYear(direction: "prev" | "next") {
    let year = parseInt(selectedYear.value);
    if (direction === "prev") {
        year--;
    } else {
        year++;
    }
    selectedYear.value = String(year);
    applyYearlyFilter();
}

function toggleStatusFilter(statusType: "expenses" | "revenues") {
    if (currentStatus.value === statusType) {
        reload({ status: null });
    } else {
        reload({ status: statusType });
    }
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

function formatDisplayDate(dateStr: string) {
    if (!dateStr) return "";
    const parts = dateStr.split("-");
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
}

function getStatusBadge(status: string) {
    switch (status) {
        case "paid":
            return {
                label: "Liquidada",
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
</script>

<template>
    <Head title="Fluxo de Caixa" />
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 border-b border-border pb-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Fluxo de Caixa
                </h2>
                <p class="text-sm text-muted-foreground">
                    Acompanhe as entradas, saídas e previsões de saldo das suas
                    contas.
                </p>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div class="flex flex-col gap-6">
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="flex flex-wrap items-end gap-6">
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
                                    Mensal
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 cursor-pointer rounded-sm text-xs font-medium"
                                    :class="{
                                        'border border-border/50 bg-white text-foreground shadow-sm':
                                            filterMode === 'yearly',
                                    }"
                                    @click="
                                        filterMode = 'yearly';
                                        applyYearlyFilter();
                                    "
                                >
                                    Anual
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
                                    Personalizado
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
                            >
                                Período
                            </label>
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

                        <!-- Yearly mode controls -->
                        <div
                            v-else-if="filterMode === 'yearly'"
                            class="space-y-1.5"
                        >
                            <label
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Ano
                            </label>
                            <div
                                class="flex items-center gap-1 rounded-lg border border-border bg-background p-1 shadow-xs"
                            >
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-9 w-9 cursor-pointer"
                                    @click="navigateYear('prev')"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </Button>

                                <div class="flex items-center gap-2 px-3">
                                    <span
                                        class="min-w-[80px] text-center text-sm font-semibold text-foreground"
                                    >
                                        {{ selectedYear }}
                                    </span>
                                </div>

                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-9 w-9 cursor-pointer"
                                    @click="navigateYear('next')"
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
                                            :max-value="endCalendarDate"
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
                                            :min-value="startCalendarDate"
                                            locale="pt-BR"
                                            initial-focus
                                        />
                                    </PopoverContent>
                                </Popover>
                            </div>
                            <Button
                                @click="reload({ start: customStart, end: customEnd })"
                                class="h-10 cursor-pointer"
                            >
                                Filtrar
                            </Button>
                        </div>

                        <!-- Bank account filter -->
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Contas
                            </label>
                            <Select
                                :model-value="
                                    props.bankAccount?.id
                                        ? String(props.bankAccount.id)
                                        : 'all'
                                "
                                @update:model-value="
                                    (val) => reload({ account_id: val })
                                "
                            >
                                <SelectTrigger
                                    class="h-10 w-[220px] border border-border bg-background text-sm"
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
                        </div>

                        <!-- Category filter -->
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Categorias
                            </label>
                            <Select
                                :model-value="
                                    props.categoryId
                                        ? String(props.categoryId)
                                        : 'all'
                                "
                                @update:model-value="
                                    (val) =>
                                        reload({
                                            category_id:
                                                val === 'all' ? null : val,
                                        })
                                "
                            >
                                <SelectTrigger
                                    class="h-10 w-[220px] border border-border bg-background text-sm"
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
                    </div>

                    <!-- Records pagination dropdown -->
                    <div class="space-y-1.5 lg:max-w-xs">
                        <label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Mostrar registros
                        </label>
                        <Select
                            :model-value="String(props.perPage || 10)"
                            @update:model-value="
                                (val) => reload({ quantity: val })
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
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Expenses card -->
            <div
                @click="toggleStatusFilter('expenses')"
                class="cursor-pointer rounded-xl border p-5 shadow-xs transition select-none hover:shadow-sm"
                :class="
                    currentStatus === 'expenses'
                        ? 'border-rose-500 bg-rose-50/30 ring-2 ring-rose-500/20'
                        : 'border-border bg-card'
                "
            >
                <div class="space-y-2">
                    <p
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Despesas (R$)
                    </p>
                    <p class="text-3xl font-extrabold text-rose-600">
                        {{ formatMoney(props.expenses) }}
                    </p>
                </div>
            </div>

            <!-- Revenues card -->
            <div
                @click="toggleStatusFilter('revenues')"
                class="cursor-pointer rounded-xl border p-5 shadow-xs transition select-none hover:shadow-sm"
                :class="
                    currentStatus === 'revenues'
                        ? 'border-emerald-500 bg-emerald-50/30 ring-2 ring-emerald-500/20'
                        : 'border-border bg-card'
                "
            >
                <div class="space-y-2">
                    <p
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Receitas (R$)
                    </p>
                    <p class="text-3xl font-extrabold text-emerald-600">
                        {{ formatMoney(props.revenues) }}
                    </p>
                </div>
            </div>

            <!-- Total period card -->
            <div
                @click="reload({ status: null })"
                class="cursor-pointer rounded-xl border p-5 shadow-xs transition select-none hover:shadow-sm"
                :class="
                    !currentStatus
                        ? 'border-blue-500 bg-blue-50/30 ring-2 ring-blue-500/20'
                        : 'border-border bg-card'
                "
            >
                <div class="space-y-2">
                    <p
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Total do período (R$)
                    </p>
                    <p class="text-3xl font-extrabold text-blue-600">
                        {{ formatMoney(props.totalPeriod) }}
                    </p>
                </div>
            </div>

            <!-- Current balance card -->
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-xs select-none"
            >
                <div class="space-y-2">
                    <p
                        class="truncate text-xs font-bold tracking-wider text-muted-foreground uppercase"
                        :title="
                            'Saldo Atual - ' +
                            (props.bankAccount?.name || 'Todas as contas')
                        "
                    >
                        Saldo Atual -
                        {{ props.bankAccount?.name || "Todas as contas" }} (R$)
                    </p>
                    <p
                        class="text-3xl font-extrabold"
                        :class="
                            Number(
                                props.bankAccount
                                    ? props.bankAccount.current_balance
                                    : props.totalBankAccounts,
                            ) < 0
                                ? 'text-rose-600'
                                : 'text-emerald-600'
                        "
                    >
                        {{
                            formatMoney(
                                props.bankAccount
                                    ? props.bankAccount.current_balance
                                    : props.totalBankAccounts,
                            )
                        }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Table view: Launch details -->
        <div
            class="overflow-hidden rounded-xl border border-border bg-card shadow-sm"
        >
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader class="bg-muted/30">
                        <TableRow>
                            <TableHead class="font-semibold"
                                >Data de Pagamento</TableHead
                            >
                            <TableHead class="font-semibold"
                                >Conta Bancaria</TableHead
                            >
                            <TableHead class="font-semibold"
                                >Categoria</TableHead
                            >
                            <TableHead class="font-semibold">Tipo</TableHead>
                            <TableHead class="text-right font-semibold"
                                >Valor (R$)</TableHead
                            >
                            <TableHead class="w-32 text-center font-semibold"
                                >Situação</TableHead
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
                                <TableCell>{{
                                    formatDate(item.due_date)
                                }}</TableCell>
                                <TableCell class="font-medium text-foreground">
                                    {{ item.bank_account_name }}
                                </TableCell>
                                <TableCell>{{ item.category_name }}</TableCell>
                                <TableCell>{{ item.type }}</TableCell>
                                <TableCell
                                    class="text-right font-semibold"
                                    :class="
                                        item.value < 0
                                            ? 'text-rose-600'
                                            : 'text-emerald-600'
                                    "
                                >
                                    {{ item.value < 0 ? "-" : "" }}R$
                                    {{ formatMoney(Math.abs(item.value)) }}
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
                            </TableRow>
                        </template>
                        <template v-else>
                            <TableRow>
                                <TableCell
                                    colspan="6"
                                    class="h-28 text-center text-muted-foreground"
                                >
                                    Nenhum lançamento de fluxo de caixa
                                    encontrado para este período.
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination Links -->
            <div
                v-if="props.accounts?.last_page > 1"
                class="flex items-center justify-between border-t border-border bg-muted/10 px-6 py-4"
            >
                <div class="text-sm text-muted-foreground">
                    Mostrando {{ installmentsList.length }} lançamentos de
                    {{ props.accounts.total }} total
                </div>
                <div class="flex items-center gap-1.5">
                    <Button
                        v-for="(link, lIdx) in props.accounts.links"
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
                            v-slot="{ href }"
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            preserve-state
                        >
                            <span v-html="link.label" />
                        </Link>
                        <span v-else v-html="link.label" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Table view: Bank overview / Future summaries -->
        <div
            class="space-y-4 rounded-xl border border-border bg-card p-6 shadow-sm"
        >
            <h3 class="text-lg font-bold text-foreground">
                Saldos e Projeções das Contas
            </h3>
            <div class="overflow-x-auto rounded-xl border border-border">
                <Table>
                    <TableHeader class="bg-muted/30">
                        <TableRow>
                            <TableHead class="font-semibold">Banco</TableHead>
                            <TableHead class="text-right font-semibold"
                                >Saldo (R$)</TableHead
                            >
                            <TableHead class="text-right font-semibold"
                                >Lançamentos futuros (R$)</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="(acc, idx) in props.bankAccounts"
                            :key="acc.id"
                        >
                            <TableCell class="font-medium text-foreground">{{
                                acc.name
                            }}</TableCell>
                            <TableCell
                                class="text-right font-semibold"
                                :class="
                                    Number(acc.current_balance || 0) < 0
                                        ? 'text-rose-600'
                                        : 'text-emerald-600'
                                "
                            >
                                R$ {{ formatMoney(acc.current_balance) }}
                            </TableCell>
                            <TableCell
                                class="text-right font-semibold"
                                :class="
                                    Number(props.accountsResult[idx] || 0) < 0
                                        ? 'text-rose-600'
                                        : Number(
                                                props.accountsResult[idx] || 0,
                                            ) > 0
                                          ? 'text-emerald-600'
                                          : 'text-muted-foreground'
                                "
                            >
                                R$
                                {{
                                    formatMoney(props.accountsResult[idx] || 0)
                                }}
                            </TableCell>
                        </TableRow>
                        <!-- Total row -->
                        <TableRow
                            class="border-t-2 border-border/80 bg-muted/40 font-bold"
                        >
                            <TableCell class="text-base text-foreground"
                                >Total</TableCell
                            >
                            <TableCell
                                class="text-right text-base font-extrabold"
                                :class="
                                    Number(props.totalBankAccounts) < 0
                                        ? 'text-rose-600'
                                        : 'text-emerald-600'
                                "
                            >
                                R$ {{ formatMoney(props.totalBankAccounts) }}
                            </TableCell>
                            <TableCell
                                class="text-right text-base font-extrabold"
                                :class="
                                    Number(props.totalOpen) < 0
                                        ? 'text-rose-600'
                                        : Number(props.totalOpen) > 0
                                          ? 'text-emerald-600'
                                          : 'text-muted-foreground'
                                "
                            >
                                R$ {{ formatMoney(props.totalOpen) }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
