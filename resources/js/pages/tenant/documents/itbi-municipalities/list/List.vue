<script setup lang="ts">
import { ServerDataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/documents/itbi-municipalities/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import type { CatalogFilters, ItbiMunicipalityRow, Paginated } from "@/types";

defineOptions({ layout: TenantLayout });

defineProps<{
    municipalities: Paginated<ItbiMunicipalityRow>;
    filters: CatalogFilters;
}>();

const { permissions } = usePermission();
</script>

<template>
    <Head title="ITBI - Municípios" />
    <div>
        <div class="mb-4 flex items-center justify-between border-b border-border pb-4">
            <h2 class="text-3xl font-bold tracking-tight text-foreground">ITBI - Municípios</h2>

            <Button
                v-if="permissions.includes('documents.itbi_municipalities.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.documents.itbi-municipalities.create')"><Plus /> Novo município</Link>
            </Button>
        </div>
        <ServerDataTable
            :columns="columns"
            :paginator="municipalities"
            :filters="filters"
            :url="route('tenant.documents.itbi-municipalities.list')"
            :with-trashed-filter="false"
            search-placeholder="Buscar município..."
        />
    </div>
</template>
