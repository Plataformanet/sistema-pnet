<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    ChartCrosshair,
    ChartConfig,
    componentToString,
} from "@/components/ui/chart";
import { VisStackedBar, VisAxis, VisXYContainer } from "@unovis/vue";
import { ref } from "vue";

// Dados simulados de faturamento
const data = ref([
    { month: "Jan", revenue: 20400 },
    { month: "Fev", revenue: 28500 },
    { month: "Mar", revenue: 32000 },
    { month: "Abr", revenue: 29800 },
    { month: "Mai", revenue: 38400 },
    { month: "Jun", revenue: 45231 },
    { month: "Jul", revenue: 48500 },
    { month: "Ago", revenue: 51200 },
    { month: "Set", revenue: 54900 },
    { month: "Out", revenue: 59300 },
    { month: "Nov", revenue: 64100 },
    { month: "Dez", revenue: 72500 },
]);

// Configuração de cores e label para o Chart do Shadcn
const config = {
    revenue: {
        // label: "Faturamento",
        color: "var(--chart-2)",
    },
} satisfies ChartConfig;
</script>

<template>
    <Card class="flex flex-col h-full">
        <CardHeader class="pb-6">
            <CardTitle class="text-lg">Faturamento Anual</CardTitle>
            <CardDescription
                >Receita financeira consolidada dos últimos 12
                meses.</CardDescription
            >
        </CardHeader>
        <CardContent
            class="flex-1 min-h-[250px] p-0 pr-6 pb-6 pt-0 flex items-center justify-center"
        >
            <ChartContainer :config="config" class="h-[250px] w-full">
                <VisXYContainer
                    :data="data"
                    :padding="{ top: 10, right: 10, bottom: 20, left: 45 }"
                >
                    <!-- Barras do gráfico -->
                    <VisStackedBar
                        :x="(d: any, i: number) => i"
                        :y="(d: any) => d.revenue"
                        color="var(--color-revenue)"
                        :roundedCorners="4"
                        :barPadding="0.15"
                    />

                    <!-- Eixo X (Meses) -->
                    <VisAxis
                        type="x"
                        :tickValues="data.map((_, i) => i)"
                        :tickFormat="(i: number) => data[i]?.month ?? ''"
                        :gridLine="false"
                        :tickLine="false"
                        :domainLine="false"
                    />
                    <!-- Eixo Y (Valores Monetários) -->
                    <VisAxis
                        type="y"
                        :tickFormat="(tick: number) => `R$${tick / 1000}k`"
                        :gridLine="false"
                        :tickLine="false"
                        :domainLine="false"
                    />

                    <!-- Tooltips usando apenas componentes do shadcn -->
                    <ChartCrosshair
                        :template="
                            componentToString(config, ChartTooltipContent, {
                                labelKey: 'month',
                                hideIndicator: true,
                                indicator: 'line',
                                valueFormatter: (val: number) =>
                                    new Intl.NumberFormat('pt-BR', {
                                        style: 'currency',
                                        currency: 'BRL',
                                    }).format(val),
                            })
                        "
                    />
                    <ChartTooltip />
                </VisXYContainer>
            </ChartContainer>
        </CardContent>
    </Card>
</template>
