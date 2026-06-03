<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/finance/subcategories/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import { FinanceSubcategory } from "@/types";

defineOptions({ layout: TenantLayout });

defineProps<{
    subcategories: FinanceSubcategory[];
}>();

const { permissions } = usePermission();
</script>

<template>
    <Head title="Subcategorias Financeiras" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Subcategorias Financeiras
                </h2>
            </div>

            <Button
                v-if="permissions.includes('finance.subcategories.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.finance.subcategories.create')"
                    ><Plus /> Nova subcategoria</Link
                >
            </Button>
        </div>
        <DataTable :columns="columns" :data="subcategories" />
    </div>
</template>
