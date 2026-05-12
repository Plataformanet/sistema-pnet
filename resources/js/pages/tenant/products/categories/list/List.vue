<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/products/categories/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { computed, ref } from "vue";

defineOptions({ layout: TenantLayout });

export interface Category {
    id: string;
    name: string;
    status: boolean;
}

defineProps<{
    categories: Category[];
}>();

const flash = computed(() => usePage().props.flash as any);
const showFlash = ref(true);
</script>

<template>
    <Head title="Categorias de Produtos" />
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
