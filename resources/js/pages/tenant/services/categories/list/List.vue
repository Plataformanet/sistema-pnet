<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/services/categories/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";

defineOptions({ layout: TenantLayout });

export interface Category {
    id: string;
    name: string;
    status: boolean;
}

defineProps<{
    categories: Category[];
}>();

const { permissions } = usePermission();
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

            <Button v-if="permissions.includes('services.categories.create')" class="cursor-pointer" as-child variant="outline">
                <Link :href="route('tenant.services.categories.create')"><Plus /> Nova categoria</Link>
            </Button>
        </div>
        <DataTable :columns="columns" :data="categories" />
    </div>
</template>
