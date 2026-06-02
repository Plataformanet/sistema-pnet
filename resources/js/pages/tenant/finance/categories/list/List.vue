<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/finance/categories/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import { FinanceCategory } from "@/types";

defineOptions({ layout: TenantLayout });

defineProps<{
    categories: FinanceCategory[];
}>();

const { permissions } = usePermission();
</script>

<template>
    <Head title="Categorias Financeiras" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Categorias Financeiras
                </h2>
            </div>

            <Button
                v-if="permissions.includes('finance.categories.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.finance.categories.create')"
                    ><Plus /> Nova categoria</Link
                >
            </Button>
        </div>
        <DataTable :columns="columns" :data="categories" />
    </div>
</template>
