<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/products/products/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";

defineOptions({ layout: TenantLayout });

export interface Product {
    id: string;
    name: string;
    sku: string;
    barcode?: string;
    category_id: string;
    cost_value?: number;
    sell_value?: number;
    manage_stock: boolean;
    current_stock?: number;
    min_stock?: number;
    unit_of_measure: string;
    description?: string;
    active: boolean;
}

const products: Product[] = [
    {
        id: "1",
        name: "Mouse Óptico Sem Fio Microsoft",
        sku: "INFO-MOU-001",
        barcode: "7891234567890",
        cost_value: 4500,
        sell_value: 12000,
        category_id: "1",
        manage_stock: true,
        current_stock: 50,
        min_stock: 10,
        unit_of_measure: "un",
        active: true,
    },
    {
        id: "2",
        name: "Teclado Mecânico RGB",
        sku: "INFO-KEY-002",
        barcode: "7891234567891",
        cost_value: 15000,
        sell_value: 35000,
        category_id: "2",
        manage_stock: true,
        current_stock: 25,
        min_stock: 5,
        unit_of_measure: "un",
        active: true,
    },
    {
        id: "3",
        name: "Papel Sulfite A4 500 Folhas",
        sku: "PAP-SUL-001",
        barcode: "7891234567892",
        cost_value: 1800,
        sell_value: 2800,
        category_id: "3",
        manage_stock: true,
        current_stock: 100,
        min_stock: 20,
        unit_of_measure: "cx",
        active: false,
    },
];
</script>

<template>
    <Head title="Lista de produtos" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Lista de produtos
                </h2>
            </div>

            <Button class="cursor-pointer" as-child variant="outline">
                <Link :href="route('tenant.products.products.create')"
                    ><Plus /> Novo produto</Link
                >
            </Button>
        </div>
        <DataTable :columns="columns" :data="products" />
    </div>
</template>
