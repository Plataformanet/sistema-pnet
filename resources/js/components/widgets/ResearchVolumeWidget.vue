<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    CardFooter,
} from "@/components/ui/card";
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    ChartConfig,
    componentToString,
} from "@/components/ui/chart";
import { VisSingleContainer, VisDonut } from "@unovis/vue";
import { Donut } from "@unovis/ts";
import { Search, Map } from "lucide-vue-next";

// Dados simulados
const data = [
    { name: "Veicular", count: 320, fill: "var(--color-Veicular)" },
    { name: "Imobiliária", count: 185, fill: "var(--color-Imobiliária)" },
    { name: "Cadastral", count: 110, fill: "var(--color-Cadastral)" },
    { name: "Crédito", count: 85, fill: "var(--color-Crédito)" },
];

const config = {
    count: { label: "Volume" }, // Necessário para o Tooltip do Shadcn renderizar a chave
    Veicular: { label: "Pesquisa Veicular", color: "var(--chart-1)" },
    Imobiliária: { label: "Pesquisa Imobiliária", color: "var(--chart-2)" },
    Cadastral: { label: "Pesquisa Cadastral", color: "var(--chart-3)" },
    Crédito: { label: "Análise de Crédito", color: "var(--chart-4)" },
} satisfies ChartConfig;

const valueAccessor = (d: any) => d.count;
</script>

<template>
    <Card class="col-span-1 flex h-full flex-col md:col-span-2 lg:col-span-3">
        <CardHeader class="border-b border-border/20 pb-2">
            <CardTitle class="flex items-center gap-2 text-lg">
                Volume por Tipo
                <Search class="h-4 w-4 text-purple-500" />
            </CardTitle>
            <CardDescription>
                Proporção de pesquisas (Mês Atual).
            </CardDescription>
        </CardHeader>

        <CardContent class="flex-1 pt-6 pb-4">
            <!-- Gráfico Donut via Unovis / Shadcn Chart -->
            <ChartContainer
                :config="config"
                class="mx-auto aspect-[1.5] max-w-[280px] pb-0"
            >
                <VisSingleContainer
                    :data="data"
                    :margin="{ top: 0, bottom: 0, left: 0, right: 0 }"
                >
                    <VisDonut
                        :value="valueAccessor"
                        :color="
                            (d: any) => {
                                const key = d.data ? d.data.name : d.name;
                                return `var(--color-${key})`;
                            }
                        "
                        :show-background="false"
                        :arc-width="25"
                    />

                    <ChartTooltip
                        :triggers="{
                            [Donut.selectors.segment]: componentToString(
                                config,
                                ChartTooltipContent,
                                { labelKey: 'name' },
                            ),
                        }"
                    />
                </VisSingleContainer>
            </ChartContainer>

            <!-- Legenda Customizada Minimalista -->
            <div class="mt-6 grid grid-cols-2 gap-3 px-2">
                <div
                    v-for="item in data"
                    :key="item.name"
                    class="flex items-center gap-2"
                >
                    <div
                        class="h-2.5 w-2.5 shrink-0 rounded-full"
                        :style="{
                            backgroundColor: `var(--color-${item.name})`,
                        }"
                    ></div>
                    <span class="truncate text-xs text-muted-foreground">{{
                        item.name
                    }}</span>
                    <span
                        class="ml-auto text-xs font-semibold text-foreground tabular-nums"
                        >{{ item.count }}</span
                    >
                </div>
            </div>
        </CardContent>
        <CardFooter class="mt-auto border-t pt-4">
            <div
                class="mt-4 flex w-full items-center justify-between text-xs text-muted-foreground"
            >
                <span class="font-medium">Total de Pesquisas:</span>
                <span class="font-bold text-foreground tabular-nums">700</span>
            </div>
        </CardFooter>
    </Card>
</template>
