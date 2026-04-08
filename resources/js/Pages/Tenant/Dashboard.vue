<script setup lang="ts">
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import CriticalIssuesWidget from "@/components/widgets/CriticalIssuesWidget.vue";
import PlaceholderWidget from "@/components/widgets/PlaceholderWidget.vue";
import MonthRecipesWidget from "@/components/widgets/MonthRecipesWidget.vue";
import MonthExpensesWidget from "@/components/widgets/MonthExpensesWidget.vue";
import CurrentBalanceWidget from "@/components/widgets/CurrentBalanceWidget.vue";
import DelinquencyWidget from "@/components/widgets/DelinquencyWidget.vue";
import RevenueChartWidget from "@/components/widgets/RevenueChartWidget.vue";
import RecentApprovalsWidget from "@/components/widgets/RecentApprovalsWidget.vue";
import UpcomingPaymentsWidget from "@/components/widgets/UpcomingPaymentsWidget.vue";
import OpenTicketsWidget from "@/components/widgets/OpenTicketsWidget.vue";
import TeamPerformanceWidget from "@/components/widgets/TeamPerformanceWidget.vue";
import ResearchVolumeWidget from "@/components/widgets/ResearchVolumeWidget.vue";

defineOptions({ layout: TenantLayout });
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex-1 space-y-6 pt-2 pb-8">
        <!-- Cabeçalho do Dashboard -->
        <div
            class="flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Dashboard
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Visão geral e atalhos rápidos
                </p>
            </div>
        </div>

        <!-- Linha 1: Grade de KPIs (4 colunas) -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MonthRecipesWidget />
            <MonthExpensesWidget />
            <CurrentBalanceWidget class="hidden md:flex" />
            <DelinquencyWidget class="hidden md:flex" />
        </div>

        <!-- Linha 2: Grade Mista para Widgets Maiores -->
        <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-7">
            <!-- Nosso Widget de Lista Ocupando 4 colunas em telas grandes -->

            <UpcomingPaymentsWidget
                class="col-span-1 md:col-span-2 lg:col-span-3"
            />

            <!-- Nosso novo Gráfico de Faturamento ocupando 3 colunas -->
            <RevenueChartWidget
                class="col-span-1 md:col-span-2 lg:col-span-4"
            />

            <!-- Widget de Aprovações Recentes -->
            <RecentApprovalsWidget
                class="col-span-1 md:col-span-2 lg:col-span-4"
            />
            <CriticalIssuesWidget
                class="col-span-1 md:col-span-2 lg:col-span-3"
            />
            <!-- Último Widget: Próximos Vencimentos -->
        </div>

        <!-- Linha 3: Módulo de Serviços (Atendimento) -->
        <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-7">
            <OpenTicketsWidget class="col-span-1 md:col-span-2 lg:col-span-4" />
            <TeamPerformanceWidget
                class="col-span-1 md:col-span-2 lg:col-span-3"
            />
        </div>

        <!-- Linha 4: Módulo de Pesquisas -->
        <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-7">
            <ResearchVolumeWidget
                class="col-span-1 md:col-span-2 lg:col-span-3"
            />
            <PlaceholderWidget class="col-span-1 md:col-span-2 lg:col-span-4" />
        </div>
    </div>
</template>
