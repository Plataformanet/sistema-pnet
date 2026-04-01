<script setup lang="ts">
import { useTenant } from "@/composables/useTenant";
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

defineOptions({ layout: TenantLayout });

const { tenant } = useTenant();
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex-1 space-y-6 pt-2 pb-8">
        <!-- Cabeçalho do Dashboard -->
        <div
            class="flex items-center justify-between pb-4 border-b border-border"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Dashboard
                </h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    Visão geral e atalhos rápidos da {{ tenant?.name }}
                </p>
            </div>
            <div>
                <Link
                    :href="route('tenant.logout')"
                    class="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                    view-transition
                    >Sair</Link
                >
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
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7 mt-6">
            <!-- Nosso Widget de Lista Ocupando 4 colunas em telas grandes -->
            <CriticalIssuesWidget
                class="col-span-1 md:col-span-2 lg:col-span-4"
            />
            
            <!-- Nosso novo Gráfico de Faturamento ocupando 3 colunas -->
            <RevenueChartWidget class="col-span-1 md:col-span-2 lg:col-span-3" />
            
            <PlaceholderWidget class="col-span-1 md:col-span-2 lg:col-span-3" />
            <PlaceholderWidget class="col-span-1 md:col-span-2 lg:col-span-4" />
        </div>
    </div>
</template>
