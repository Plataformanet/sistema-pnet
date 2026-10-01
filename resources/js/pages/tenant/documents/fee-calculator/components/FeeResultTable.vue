<script setup lang="ts">
import { computed } from "vue";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { formatMoney } from "@/lib/masks";
import type { FeeBreakdown } from "@/types";

/**
 * Só renderiza o resultado montado no servidor (totais incluídos).
 * `extra_information` já chega sanitizado.
 */
const props = defineProps<{
    breakdown: FeeBreakdown;
}>();

const cell = (value: string | number | null | undefined, numeric: boolean) =>
    numeric ? formatMoney(Number(value ?? 0)) : (value ?? "");

/** Taxas extras e totais ocupam as duas últimas colunas (rótulo + valor). */
const leadingSpan = computed(() => Math.max(props.breakdown.columns.length - 2, 0));
</script>

<template>
    <div class="space-y-4">
        <div class="overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow class="border-b-2 border-foreground/40 hover:bg-transparent">
                        <TableHead v-for="column in breakdown.columns" :key="column.key" class="font-semibold">{{ column.label }}</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="(act, index) in breakdown.acts" :key="index">
                        <TableCell
                            v-for="column in breakdown.columns"
                            :key="column.key"
                            :class="column.numeric ? 'whitespace-nowrap' : ''"
                            >{{ cell(act[column.key], column.numeric) }}</TableCell
                        >
                    </TableRow>
                    <TableRow v-if="!breakdown.acts.length">
                        <TableCell :colspan="Math.max(breakdown.columns.length, 1)" class="h-16 text-center"
                            >Nenhum registro encontrado</TableCell
                        >
                    </TableRow>
                    <TableRow v-else class="font-semibold">
                        <TableCell v-for="(column, index) in breakdown.columns" :key="column.key" class="whitespace-nowrap">
                            {{ column.numeric ? formatMoney(breakdown.subtotals[column.key] ?? 0) : index === 0 ? "SUBTOTAIS" : "" }}
                        </TableCell>
                    </TableRow>

                    <TableRow v-for="(fee, index) in breakdown.extra_fees" :key="`fee-${index}`">
                        <TableCell v-if="leadingSpan" :colspan="leadingSpan" />
                        <TableCell>{{ fee.description }}</TableCell>
                        <TableCell class="whitespace-nowrap">{{ formatMoney(fee.amount) }}</TableCell>
                    </TableRow>

                    <TableRow class="border-t-2 border-foreground/40 font-semibold">
                        <TableCell v-if="leadingSpan" :colspan="leadingSpan" />
                        <TableCell>{{ breakdown.show_grand_total ? "Total do cálculo" : "TOTAL" }}</TableCell>
                        <TableCell class="whitespace-nowrap">{{ formatMoney(breakdown.calculation_total) }}</TableCell>
                    </TableRow>
                    <TableRow v-if="breakdown.itbi !== null" class="font-semibold">
                        <TableCell v-if="leadingSpan" :colspan="leadingSpan" />
                        <TableCell>ITBI</TableCell>
                        <TableCell class="whitespace-nowrap">{{ formatMoney(breakdown.itbi) }}</TableCell>
                    </TableRow>
                    <TableRow v-for="(service, index) in breakdown.services" :key="`service-${index}`">
                        <TableCell v-if="leadingSpan" :colspan="leadingSpan" />
                        <TableCell>{{ service.name }}</TableCell>
                        <TableCell class="whitespace-nowrap">{{ formatMoney(service.amount) }}</TableCell>
                    </TableRow>
                    <TableRow v-if="breakdown.show_grand_total" class="font-bold">
                        <TableCell v-if="leadingSpan" :colspan="leadingSpan" />
                        <TableCell>TOTAL</TableCell>
                        <TableCell class="whitespace-nowrap">{{ formatMoney(breakdown.grand_total) }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div
            v-if="breakdown.extra_information"
            class="prose prose-sm max-w-none rounded-md border border-border bg-muted/30 p-4 text-sm text-muted-foreground"
            v-html="breakdown.extra_information"
        />
    </div>
</template>
