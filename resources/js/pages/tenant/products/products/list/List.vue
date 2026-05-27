<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/products/products/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { computed, ref } from "vue";
import { usePermission } from "@/composables/usePermission";

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
    status: boolean;
}

defineProps<{
    products: Product[];
}>();

const { permissions } = usePermission();
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

            <Button v-if="permissions.includes('products.products.create')" class="cursor-pointer" as-child variant="outline">
                <Link :href="route('tenant.products.products.create')"
                    ><Plus /> Novo produto</Link
                >
            </Button>
        </div>
        <DataTable :columns="columns" :data="products" />
    </div>
</template>
