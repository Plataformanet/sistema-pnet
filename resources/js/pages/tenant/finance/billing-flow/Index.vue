<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
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
import { ChevronDown, Calendar, Search } from "lucide-vue-next";
import { route } from "ziggy-js";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    data: {
        data_by_account: Record<
            number,
            {
                account: any;
                invoicing: Record<
                    number,
                    {
                        months: Record<number, number>;
                        annual_total: number;
                        percentage_variation: number;
                    }
                >;
            }
        >;
        monthly_comparison: Record<
            string,
            {
                totals_by_year: Record<number, number>;
                total_period: number;
            }
        >;
        general_summary: {
            total_active_accounts: number;
            total_period_billing: number;
            best_year: { year: number; value: number } | null;
            worst_year: { year: number; value: number } | null;
            monthly_average: number;
        };
    };
    bankAccounts: any[];
    bankAccountId: number | null;
    startYear: number;
    endYear: number;
}>();

// Filter states
const filterStartYear = ref(props.startYear);
const filterEndYear = ref(props.endYear);
const filterBankAccountId = ref(
    props.bankAccountId ? String(props.bankAccountId) : "all",
);

// Accordion expanded states
const expandedAccounts = ref<Record<number, boolean>>({});

function toggleAccount(accountId: number) {
    expandedAccounts.value[accountId] = !expandedAccounts.value[accountId];
}

// Watchers to update filter controls if props change
watch(
    () => props.startYear,
    (val) => {
        filterStartYear.value = val;
    },
);
watch(
    () => props.endYear,
    (val) => {
        filterEndYear.value = val;
    },
);
watch(
    () => props.bankAccountId,
    (val) => {
        filterBankAccountId.value = val ? String(val) : "all";
    },
);

const currentYear = new Date().getFullYear();
const startRange = 2015;
const endRange = currentYear + 5;

const yearsOptions = computed(() => {
    const list = [];
    for (let y = startRange; y <= endRange; y++) {
        list.push(y);
    }
    return list;
});

// Prevent start year from being greater than end year
watch(filterStartYear, (newStart) => {
    if (newStart > filterEndYear.value) {
        filterEndYear.value = newStart;
    }
});

watch(filterEndYear, (newEnd) => {
    if (newEnd < filterStartYear.value) {
        filterStartYear.value = newEnd;
    }
});

function handleFilter() {
    const params: any = {
        start_year: filterStartYear.value,
        end_year: filterEndYear.value,
        bank_account_id:
            filterBankAccountId.value === "all"
                ? undefined
                : filterBankAccountId.value,
    };
    router.get(route("tenant.finance.billing.index"), params, {
        preserveState: true,
        preserveScroll: true,
    });
}

const monthsKeys = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];

const monthsLabels = {
    January: "Jan",
    February: "Fev",
    March: "Mar",
    April: "Abr",
    May: "Mai",
    June: "Jun",
    July: "Jul",
    August: "Ago",
    September: "Set",
    October: "Out",
    November: "Nov",
    December: "Dez",
};

// Calculate consolidated data by year for the main table
const consolidatedDataByYear = computed(() => {
    const list = [];
    let prevAnnualTotal = 0;

    for (let y = props.startYear; y <= props.endYear; y++) {
        let annualTotal = 0;
        const monthsData: Record<number, number> = {};

        monthsKeys.forEach((monthKey, idx) => {
            const val =
                props.data.monthly_comparison[monthKey]?.totals_by_year[y] || 0;
            monthsData[idx + 1] = val;
            annualTotal += val;
        });

        let variation: number | null = null;
        if (y > props.startYear && prevAnnualTotal > 0) {
            variation =
                ((annualTotal - prevAnnualTotal) / prevAnnualTotal) * 100;
        }

        list.push({
            year: y,
            months: monthsData,
            annual_total: annualTotal,
            percentage_variation: variation,
        });

        prevAnnualTotal = annualTotal;
    }

    return list;
});

// Format helpers
function formatMoney(cents: number | string | undefined | null) {
    if (cents === undefined || cents === null) return "0,00";
    const value = typeof cents === "string" ? parseInt(cents) : cents;
    return (value / 100).toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function formatPercentage(val: number | null) {
    if (val === null || val === undefined) return "-";
    const sign = val > 0 ? "+" : "";
    return `${sign}${val.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}%`;
}
</script>

<template>
    <Head title="Fluxo de Faturamento" />
    <div class="space-y-6">
        <!-- Breadcrumbs / Title -->
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <Link
                    :href="route('tenant.dashboard')"
                    class="hover:text-foreground"
                    >Home</Link
                >
                <span>&gt;</span>
                <span class="text-foreground">Faturamento últimos anos</span>
            </div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Fluxo de Faturamento - Últimos Anos
            </h2>
        </div>

        <!-- Filters Block -->
        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-12 md:items-end">
                <!-- Bank Account -->
                <div class="space-y-2 md:col-span-5">
                    <label
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Conta Bancária
                    </label>
                    <Select v-model="filterBankAccountId">
                        <SelectTrigger
                            class="h-10 border border-border bg-background text-sm"
                        >
                            <SelectValue placeholder="Todas as Contas" />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectItem value="all">Todas as Contas</SelectItem>
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

                <!-- Start Year -->
                <div class="space-y-2 md:col-span-2">
                    <label
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Ano Início
                    </label>
                    <Select
                        :model-value="String(filterStartYear)"
                        @update:model-value="
                            (val: any) => (filterStartYear = Number(val))
                        "
                    >
                        <SelectTrigger
                            class="h-10 border border-border bg-background text-sm"
                        >
                            <SelectValue placeholder="Ano Início" />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectItem
                                v-for="year in yearsOptions"
                                :key="year"
                                :value="String(year)"
                                :disabled="year > filterEndYear"
                            >
                                {{ year }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- End Year -->
                <div class="space-y-2 md:col-span-2">
                    <label
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Ano Fim
                    </label>
                    <Select
                        :model-value="String(filterEndYear)"
                        @update:model-value="
                            (val: any) => (filterEndYear = Number(val))
                        "
                    >
                        <SelectTrigger
                            class="h-10 border border-border bg-background text-sm"
                        >
                            <SelectValue placeholder="Ano Fim" />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectItem
                                v-for="year in yearsOptions"
                                :key="year"
                                :value="String(year)"
                                :disabled="year < filterStartYear"
                            >
                                {{ year }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Submit Button -->
                <div class="md:col-span-3 flex justify-end">
                    <Button
                        @click="handleFilter"
                        class="flex h-10 w-full items-center justify-center gap-2 px-6 font-semibold text-white md:w-auto"
                    >
                        <Search class="h-4 w-4" />
                        Filtrar
                    </Button>
                </div>
            </div>
        </div>

        <!-- Insights Summary Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Period -->
            <div
                class="rounded-xl border border-blue-500 bg-blue-600 p-6 text-white shadow-md transition hover:shadow-lg"
            >
                <p
                    class="text-xs font-bold tracking-wider uppercase opacity-90"
                >
                    Total Período
                </p>
                <p class="mt-2 text-3xl font-extrabold">
                    R$
                    {{
                        formatMoney(
                            props.data.general_summary?.total_period_billing,
                        )
                    }}
                </p>
            </div>

            <!-- Best Year -->
            <div
                class="rounded-xl border border-emerald-500 bg-emerald-600 p-6 text-white shadow-md transition hover:shadow-lg"
            >
                <p
                    class="text-xs font-bold tracking-wider uppercase opacity-90"
                >
                    Melhor Ano
                </p>
                <template v-if="props.data.general_summary?.best_year">
                    <p class="mt-1 text-2xl font-black">
                        {{ props.data.general_summary.best_year.year }}
                    </p>
                    <p class="text-lg font-bold opacity-90">
                        R$
                        {{
                            formatMoney(
                                props.data.general_summary.best_year.value,
                            )
                        }}
                    </p>
                </template>
                <p v-else class="mt-2 text-lg font-medium opacity-90">-</p>
            </div>

            <!-- Worst Year -->
            <div
                class="rounded-xl border border-rose-500 bg-rose-600 p-6 text-white shadow-md transition hover:shadow-lg"
            >
                <p
                    class="text-xs font-bold tracking-wider uppercase opacity-90"
                >
                    Pior Ano
                </p>
                <template v-if="props.data.general_summary?.worst_year">
                    <p class="mt-1 text-2xl font-black">
                        {{ props.data.general_summary.worst_year.year }}
                    </p>
                    <p class="text-lg font-bold opacity-90">
                        R$
                        {{
                            formatMoney(
                                props.data.general_summary.worst_year.value,
                            )
                        }}
                    </p>
                </template>
                <p v-else class="mt-2 text-lg font-medium opacity-90">-</p>
            </div>

            <!-- Monthly Average -->
            <div
                class="rounded-xl border border-sky-400 bg-sky-500 p-6 text-white shadow-md transition hover:shadow-lg"
            >
                <p
                    class="text-xs font-bold tracking-wider uppercase opacity-90"
                >
                    Média Mensal
                </p>
                <p class="mt-2 text-3xl font-extrabold">
                    R$
                    {{
                        formatMoney(props.data.general_summary?.monthly_average)
                    }}
                </p>
            </div>
        </div>

        <!-- Main Comparative Billing Table -->
        <div
            class="overflow-hidden rounded-xl border border-border bg-card shadow-sm"
        >
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader class="bg-muted/30">
                        <TableRow>
                            <TableHead
                                class="min-w-[120px] font-semibold text-foreground"
                                >Ano/Calendário</TableHead
                            >
                            <TableHead
                                v-for="mKey in monthsKeys"
                                :key="mKey"
                                class="text-center font-semibold text-foreground"
                            >
                                {{
                                    monthsLabels[
                                        mKey as keyof typeof monthsLabels
                                    ]
                                }}
                            </TableHead>
                            <TableHead
                                class="min-w-[120px] text-right font-semibold text-foreground"
                                >Valor Anual</TableHead
                            >
                            <TableHead
                                class="min-w-[100px] text-center font-semibold text-foreground"
                                >Variação %</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <!-- Year Rows -->
                        <TableRow
                            v-for="row in consolidatedDataByYear"
                            :key="row.year"
                            class="hover:bg-muted/10"
                        >
                            <TableCell class="font-bold text-foreground">{{
                                row.year
                            }}</TableCell>
                            <TableCell
                                v-for="mIdx in 12"
                                :key="mIdx"
                                class="text-center font-medium text-muted-foreground"
                                :class="{
                                    'text-rose-600': row.months[mIdx] < 0,
                                }"
                            >
                                {{
                                    row.months[mIdx] !== 0
                                        ? formatMoney(row.months[mIdx])
                                        : "0,00"
                                }}
                            </TableCell>
                            <TableCell
                                class="text-right font-bold text-foreground"
                                :class="{
                                    'text-rose-600': row.annual_total < 0,
                                }"
                            >
                                {{ formatMoney(row.annual_total) }}
                            </TableCell>
                            <TableCell
                                class="text-center font-bold"
                                :class="[
                                    row.percentage_variation === null
                                        ? 'text-muted-foreground'
                                        : row.percentage_variation > 0
                                          ? 'text-emerald-600'
                                          : row.percentage_variation < 0
                                            ? 'text-rose-600'
                                            : 'text-muted-foreground',
                                ]"
                            >
                                {{ formatPercentage(row.percentage_variation) }}
                            </TableCell>
                        </TableRow>

                        <!-- Bottom Comparação por mês Row -->
                        <TableRow
                            class="border-t-2 border-border/85 bg-muted/20 font-bold text-foreground"
                        >
                            <TableCell class="font-bold text-foreground"
                                >Comparação por mês</TableCell
                            >
                            <TableCell
                                v-for="mKey in monthsKeys"
                                :key="mKey"
                                class="text-center font-bold text-foreground"
                                :class="{
                                    'text-rose-600':
                                        props.data.monthly_comparison[mKey]
                                            ?.total_period < 0,
                                }"
                            >
                                {{
                                    formatMoney(
                                        props.data.monthly_comparison[mKey]
                                            ?.total_period || 0,
                                    )
                                }}
                            </TableCell>
                            <TableCell
                                class="text-right font-extrabold text-foreground"
                                :class="{
                                    'text-rose-600':
                                        props.data.general_summary
                                            ?.total_period_billing < 0,
                                }"
                            >
                                {{
                                    formatMoney(
                                        props.data.general_summary
                                            ?.total_period_billing || 0,
                                    )
                                }}
                            </TableCell>
                            <TableCell
                                class="text-center font-medium text-muted-foreground"
                                >-</TableCell
                            >
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Bank Account Detailing (Accordions) -->
        <div class="space-y-4">
            <h3 class="text-lg font-bold text-foreground">
                Detalhamento por Conta Bancária
            </h3>
            <div class="space-y-3">
                <div
                    v-for="item in Object.values(props.data.data_by_account)"
                    :key="item.account.id"
                    class="overflow-hidden rounded-xl border border-border bg-card shadow-xs transition"
                >
                    <!-- Accordion Header Button -->
                    <button
                        @click="toggleAccount(item.account.id)"
                        class="flex w-full items-center justify-between p-5 text-left font-semibold text-foreground select-none hover:bg-muted/5"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-base font-bold">{{
                                item.account.name
                            }}</span>
                            <Badge
                                variant="outline"
                                class="border-blue-200 bg-blue-50 font-semibold text-blue-700"
                            >
                                {{ item.account.bank }}
                            </Badge>
                            <Badge
                                v-if="item.account.main_account"
                                class="border-emerald-200 bg-emerald-50 font-semibold text-emerald-700"
                            >
                                Principal
                            </Badge>
                        </div>
                        <ChevronDown
                            class="h-5 w-5 text-muted-foreground transition-transform duration-200"
                            :class="{
                                'rotate-180': expandedAccounts[item.account.id],
                            }"
                        />
                    </button>

                    <!-- Accordion Content Table -->
                    <div
                        v-show="expandedAccounts[item.account.id]"
                        class="overflow-x-auto border-t border-border bg-muted/5 p-5"
                    >
                        <Table>
                            <TableHeader class="bg-muted/20">
                                <TableRow>
                                    <TableHead
                                        class="font-semibold text-foreground"
                                        >Ano</TableHead
                                    >
                                    <TableHead
                                        v-for="mKey in monthsKeys"
                                        :key="mKey"
                                        class="text-center font-semibold text-foreground"
                                    >
                                        {{
                                            monthsLabels[
                                                mKey as keyof typeof monthsLabels
                                            ]
                                        }}
                                    </TableHead>
                                    <TableHead
                                        class="text-right font-semibold text-foreground"
                                        >Total Anual</TableHead
                                    >
                                    <TableHead
                                        class="text-center font-semibold text-foreground"
                                        >Variação %</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="(
                                        yearData, yearNum
                                    ) in item.invoicing"
                                    :key="yearNum"
                                    class="bg-background hover:bg-muted/10"
                                >
                                    <TableCell
                                        class="font-bold text-foreground"
                                        >{{ yearNum }}</TableCell
                                    >
                                    <TableCell
                                        v-for="mIdx in 12"
                                        :key="mIdx"
                                        class="text-center font-medium text-muted-foreground"
                                        :class="{
                                            'text-rose-600':
                                                yearData.months[mIdx] < 0,
                                        }"
                                    >
                                        {{
                                            yearData.months[mIdx] !== 0
                                                ? formatMoney(
                                                      yearData.months[mIdx],
                                                  )
                                                : "0,00"
                                        }}
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-bold text-foreground"
                                        :class="{
                                            'text-rose-600':
                                                yearData.annual_total < 0,
                                        }"
                                    >
                                        {{ formatMoney(yearData.annual_total) }}
                                    </TableCell>
                                    <TableCell
                                        class="text-center font-bold"
                                        :class="[
                                            Number(yearNum) === props.startYear
                                                ? 'text-muted-foreground'
                                                : yearData.percentage_variation >
                                                    0
                                                  ? 'text-emerald-600'
                                                  : yearData.percentage_variation <
                                                      0
                                                    ? 'text-rose-600'
                                                    : 'text-muted-foreground',
                                        ]"
                                    >
                                        {{
                                            Number(yearNum) === props.startYear
                                                ? "-"
                                                : formatPercentage(
                                                      yearData.percentage_variation,
                                                  )
                                        }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
