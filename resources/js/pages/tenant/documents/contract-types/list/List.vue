<script setup lang="ts">
import { ServerDataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/documents/contract-types/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import type { ContractType, CatalogFilters, Paginated } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    contractTypes: Paginated<ContractType>;
    filters: CatalogFilters;
}>();

const { permissions } = usePermission();
</script>

<template>
    <Head title="Tipos de Contrato" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Tipos de Contrato
                </h2>
            </div>

            <Button
                v-if="permissions.includes('documents.contract_types.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.documents.contract-types.create')"
                    ><Plus /> Novo tipo de contrato</Link
                >
            </Button>
        </div>
        <ServerDataTable
            :columns="columns"
            :paginator="props.contractTypes"
            :filters="props.filters"
            :url="route('tenant.documents.contract-types.list')"
        />
    </div>
</template>
