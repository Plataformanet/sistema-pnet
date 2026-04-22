<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/services/services/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";

defineOptions({ layout: TenantLayout });

export interface Service {
    id: string;
    name: string;
    sku: string;
    cost_value?: number;
    sell_value?: number;
    fees?: number;
    category_id: string;
    description?: string;
    duration?: string;
    active: boolean;
}

const services: Service[] = [
    {
        id: "1",
        name: "Consultoria Inicial",
        sku: "SRV-001",
        cost_value: 0,
        sell_value: 15000,
        fees: 0,
        category_id: "1",
        description: "Reunião de alinhamento com o cliente para entender a demanda.",
        duration: "60",
        active: true,
    },
    {
        id: "2",
        name: "Desenvolvimento de Projeto",
        sku: "SRV-002",
        cost_value: 50000,
        sell_value: 250000,
        fees: 10000,
        category_id: "2",
        description: "Elaboração de um projeto completo com base no escopo definido.",
        duration: "1800",
        active: true,
    },
    {
        id: "3",
        name: "Suporte Técnico Mensal",
        sku: "SRV-003",
        cost_value: 15000,
        sell_value: 60000,
        fees: 5000,
        category_id: "3",
        description: "Pacote de suporte técnico com até 10 horas mensais.",
        duration: "600",
        active: false,
    },
];
</script>

<template>
    <Head title="Lista de serviços" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Lista de serviços
                </h2>
            </div>

            <Button class="cursor-pointer" as-child variant="outline">
                <Link :href="route('tenant.services.services.create')"
                    ><Plus /> Novo serviço</Link
                >
            </Button>
        </div>
        <DataTable :columns="columns" :data="services" />
    </div>
</template>
