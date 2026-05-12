<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/products/products/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { computed, ref } from "vue";

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

const flash = computed(() => usePage().props.flash as any);
const showFlash = ref(true);
</script>

<template>
    <Head title="Lista de produtos" />
    <div>
        <div v-if="flash?.success && showFlash" class="mb-4 flex items-center justify-between rounded-md bg-green-100 p-4 text-green-800">
            {{ flash.success }}
            <button @click="showFlash = false" class="ml-4 font-bold cursor-pointer">&times;</button>
        </div>
        <div v-if="flash?.error && showFlash" class="mb-4 flex items-center justify-between rounded-md bg-red-100 p-4 text-red-800">
            {{ flash.error }}
            <button @click="showFlash = false" class="ml-4 font-bold cursor-pointer">&times;</button>
        </div>
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
