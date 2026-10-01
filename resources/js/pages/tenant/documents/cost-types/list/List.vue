<script setup lang="ts">
import { ServerDataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { buildColumns } from "@/pages/tenant/documents/cost-types/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import type { CostType, CatalogFilters, Paginated, Option } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    costTypes: Paginated<CostType>;
    filters: CatalogFilters;
    receiptTypes?: Option[];
}>();

const { permissions } = usePermission();
</script>

<template>
    <Head title="Tipos de Custo" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Tipos de Custo
                </h2>
            </div>

            <Button
                v-if="permissions.includes('documents.cost_types.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.documents.cost-types.create')"
                    ><Plus /> Novo tipo de custo</Link
                >
            </Button>
        </div>
        <ServerDataTable
            :columns="buildColumns(props.receiptTypes ?? [])"
            :paginator="props.costTypes"
            :filters="props.filters"
            :url="route('tenant.documents.cost-types.list')"
        />
    </div>
</template>
