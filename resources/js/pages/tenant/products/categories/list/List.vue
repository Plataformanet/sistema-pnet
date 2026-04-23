<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/products/categories/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";

defineOptions({ layout: TenantLayout });

export interface Category {
    id: string;
    name: string;
    active: boolean;
}

const categories: Category[] = [
    {
        id: "1",
        name: "Informática / Computadores",
        active: true,
    },
    {
        id: "2",
        name: "Periféricos",
        active: true,
    },
    {
        id: "3",
        name: "Suprimentos e Papelaria",
        active: false,
    },
];
</script>

<template>
    <Head title="Categorias de Produtos" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Categorias de Produtos
                </h2>
            </div>

            <Button class="cursor-pointer" as-child variant="outline">
                <Link :href="route('tenant.products.categories.create')"
                    ><Plus /> Nova categoria</Link
                >
            </Button>
        </div>
        <DataTable :columns="columns" :data="categories" />
    </div>
</template>
