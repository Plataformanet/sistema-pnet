<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/services/categories/list/columns";
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
        name: "Consultoria",
        active: true,
    },
    {
        id: "2",
        name: "Desenvolvimento",
        active: true,
    },
    {
        id: "3",
        name: "Suporte e Manutenção",
        active: false,
    },
];
</script>

<template>
    <Head title="Lista de categorias" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Categorias de Serviços
                </h2>
            </div>

            <Button class="cursor-pointer" as-child variant="outline">
                <Link :href="route('tenant.services.categories.create')"
                    ><Plus /> Nova categoria</Link
                >
            </Button>
        </div>
        <DataTable :columns="columns" :data="categories" />
    </div>
</template>
