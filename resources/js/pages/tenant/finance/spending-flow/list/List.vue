<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
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
import { ChevronLeft, ChevronRight, ChevronDown } from "lucide-vue-next";
import { route } from "ziggy-js";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    year: string | number;
    categoryId?: string | number | null;
    months: Record<number, string>;
    spendingFlow: {
        categories: any[];
        totalsByMonth: Record<number, number>;
        grandTotal: number;
        monthlyAverage: number;
        dailyAverage: number;
    };
    financialCategories: any[];
}>();

const selectedYear = ref(Number(props.year));
const selectedCategory = ref(props.categoryId ? String(props.categoryId) : "all");

// Update internal state when props change
watch(() => props.year, (newYear) => {
    selectedYear.value = Number(newYear);
});

watch(() => props.categoryId, (newCatId) => {
    selectedCategory.value = newCatId ? String(newCatId) : "all";
});

function reload(extraParams = {}) {
    const params: any = {
        year: selectedYear.value,
        category_id: selectedCategory.value === "all" ? undefined : selectedCategory.value,
        ...extraParams,
    };

    router.get(route("tenant.finance.spending-flow.index"), params, {
        preserveState: true,
        preserveScroll: true,
    });
}

function navigateYear(direction: "prev" | "next") {
    if (direction === "prev") {
        selectedYear.value--;
    } else {
        selectedYear.value++;
    }
    reload();
}

function handleCategoryChange(val: any) {
    selectedCategory.value = val;
    reload({ category_id: val === "all" ? undefined : val });
}

const expandedCategories = ref<Record<number, boolean>>({});

function toggleCategory(categoryId: number) {
    expandedCategories.value[categoryId] = !expandedCategories.value[categoryId];
}

// Helpers
function formatMoney(cents: number | string | undefined | null) {
    if (cents === undefined || cents === null) return "0,00";
    const value = typeof cents === "string" ? parseInt(cents) : cents;
    return (value / 100).toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}
</script>

<template>
    <Head title="Fluxo de Gastos" />
    <div class="space-y-6">
        <!-- Header / Breadcrumbs -->
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <Link :href="route('tenant.dashboard')" class="hover:text-foreground">Home</Link>
                <span>&gt;</span>
                <span class="text-foreground">Lista de Fluxos de Gastos</span>
            </div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Fluxo de Gastos
            </h2>
        </div>

        <!-- Filters & Actions -->
        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div class="flex flex-wrap items-end gap-6">
                    <!-- Period (Year Carousel) -->
                    <div class="space-y-2">
                        <label class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                            Período
                        </label>
                        <div class="flex items-center gap-1 rounded-lg border border-border bg-background p-1 shadow-xs">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-9 w-9 cursor-pointer"
                                @click="navigateYear('prev')"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            <span class="min-w-[120px] text-center text-sm font-semibold text-foreground">
                                Ano de {{ selectedYear }}
                            </span>
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

                    <!-- Category Filter -->
                    <div class="space-y-2">
                        <label class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                            Categorias
                        </label>
                        <Select
                            :model-value="selectedCategory"
                            @update:model-value="handleCategoryChange"
                        >
                            <SelectTrigger class="h-10 w-[240px] border border-border bg-background text-sm">
                                <SelectValue placeholder="Todas as Categorias" />
                            </SelectTrigger>
                            <SelectContent side="bottom">
                                <SelectItem value="all">Todas as Categorias</SelectItem>
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

                <!-- Export PDF Action Button -->
                <div>
                    <Button
                        variant="destructive"
                        class="h-10 bg-red-600 hover:bg-red-700 text-white font-bold"
                        as-child
                    >
                        <a
                            :href="route('tenant.finance.spending-flow.pdf', {
                                year: selectedYear,
                                category_id: selectedCategory === 'all' ? undefined : selectedCategory
                            })"
                            target="_blank"
                        >
                            Gerar PDF
                        </a>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Main Spending Flow Table -->
        <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader class="bg-muted/30">
                        <TableRow>
                            <TableHead class="font-semibold text-foreground min-w-[200px]">Categorias</TableHead>
                            <TableHead
                                v-for="m in Object.keys(props.months)"
                                :key="m"
                                class="text-center font-semibold text-foreground"
                            >
                                {{ props.months[Number(m)] }}
                            </TableHead>
                            <TableHead class="text-right font-semibold text-foreground min-w-[120px]">Total (R$)</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="props.spendingFlow?.categories?.length > 0">
                            <template
                                v-for="(entry, index) in props.spendingFlow.categories"
                                :key="index"
                            >
                                <!-- Parent Category Row -->
                                <TableRow
                                    class="bg-muted/30 hover:bg-muted/40 font-bold"
                                    :class="{ 'cursor-pointer select-none': entry.has_subcategories }"
                                    @click="entry.has_subcategories && toggleCategory(entry.category.id)"
                                >
                                    <TableCell class="text-foreground font-semibold">
                                        <div class="flex items-center gap-2">
                                            <ChevronRight
                                                v-if="entry.has_subcategories"
                                                class="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-300"
                                                :class="{ 'rotate-90': expandedCategories[entry.category.id] }"
                                            />
                                            <span v-else class="w-4 h-4 shrink-0 inline-block"></span>
                                            <span>{{ entry.category?.name }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell
                                        v-for="m in Object.keys(props.months)"
                                        :key="m"
                                        class="text-center font-medium text-muted-foreground"
                                    >
                                        {{ entry.months[Number(m)] > 0 ? formatMoney(entry.months[Number(m)]) : '-' }}
                                    </TableCell>
                                    <TableCell class="text-right font-bold text-foreground">
                                        {{ formatMoney(entry.total) }}
                                    </TableCell>
                                </TableRow>

                                <!-- Subcategory Rows (rendered if parent has subcategories and is expanded) -->
                                <TransitionGroup name="row-fade">
                                    <TableRow
                                        v-if="entry.has_subcategories && expandedCategories[entry.category.id]"
                                        v-for="(sub, subIdx) in entry.subcategories"
                                        :key="`sub-${sub.subcategory.id || subIdx}`"
                                        class="hover:bg-muted/10 transition-all duration-300"
                                    >
                                        <TableCell class="pl-14 font-normal text-muted-foreground">
                                            └─ {{ sub.subcategory?.name }}
                                        </TableCell>
                                        <TableCell
                                            v-for="m in Object.keys(props.months)"
                                            :key="m"
                                            class="text-center text-muted-foreground"
                                        >
                                            {{ sub.months[Number(m)] > 0 ? formatMoney(sub.months[Number(m)]) : '-' }}
                                        </TableCell>
                                        <TableCell class="text-right font-semibold text-muted-foreground">
                                            {{ formatMoney(sub.total) }}
                                        </TableCell>
                                    </TableRow>
                                </TransitionGroup>
                            </template>

                            <!-- Valores somados (R$) Row -->
                            <TableRow class="bg-muted/20 border-t-2 border-border/80 font-bold text-foreground">
                                <TableCell class="font-bold text-foreground">
                                    Valores somados (R$)
                                </TableCell>
                                <TableCell
                                    v-for="m in Object.keys(props.months)"
                                    :key="m"
                                    class="text-center font-bold text-foreground"
                                >
                                    {{ props.spendingFlow.totalsByMonth[Number(m)] > 0 ? formatMoney(props.spendingFlow.totalsByMonth[Number(m)]) : '-' }}
                                </TableCell>
                                <TableCell class="text-right font-extrabold text-foreground">
                                    {{ formatMoney(props.spendingFlow.grandTotal) }}
                                </TableCell>
                            </TableRow>

                            <!-- Valor Geral Row -->
                            <TableRow class="bg-muted/10 border-t border-border font-bold">
                                <TableCell class="font-semibold text-foreground">
                                    Valor Geral
                                </TableCell>
                                <TableCell
                                    v-for="m in Object.keys(props.months)"
                                    :key="m"
                                    class="text-center text-muted-foreground"
                                >
                                    -
                                </TableCell>
                                <TableCell class="text-right font-extrabold text-foreground">
                                    {{ formatMoney(props.spendingFlow.grandTotal) }}
                                </TableCell>
                            </TableRow>

                            <!-- Média mensal Row -->
                            <TableRow class="bg-muted/10 border-t border-border font-bold">
                                <TableCell class="font-semibold text-foreground">
                                    Média mensal
                                </TableCell>
                                <TableCell
                                    v-for="m in Object.keys(props.months)"
                                    :key="m"
                                    class="text-center text-muted-foreground"
                                >
                                    -
                                </TableCell>
                                <TableCell class="text-right font-extrabold text-foreground">
                                    {{ formatMoney(props.spendingFlow.monthlyAverage) }}
                                </TableCell>
                            </TableRow>

                            <!-- Valor diário Row -->
                            <TableRow class="bg-muted/10 border-t border-border font-bold">
                                <TableCell class="font-semibold text-foreground">
                                    Valor diário
                                </TableCell>
                                <TableCell
                                    v-for="m in Object.keys(props.months)"
                                    :key="m"
                                    class="text-center text-muted-foreground"
                                >
                                    -
                                </TableCell>
                                <TableCell class="text-right font-extrabold text-foreground">
                                    {{ formatMoney(props.spendingFlow.dailyAverage) }}
                                </TableCell>
                            </TableRow>
                        </template>

                        <!-- No Data Row -->
                        <template v-else>
                            <TableRow>
                                <TableCell
                                    :colspan="14"
                                    class="h-32 text-center text-muted-foreground"
                                >
                                    Nenhuma categoria de gasto encontrada para este período.
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>
        </div>
    </div>
</template>

<style scoped>
.row-fade-enter-active,
.row-fade-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.row-fade-enter-active td,
.row-fade-leave-active td {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.row-fade-enter-from,
.row-fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
    border-color: transparent !important;
}

.row-fade-enter-from td,
.row-fade-leave-to td {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    line-height: 0 !important;
    font-size: 0 !important;
}
</style>
