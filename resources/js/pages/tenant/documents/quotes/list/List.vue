<script setup lang="ts">
import { ServerDataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/documents/quotes/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Calculator } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import type { CatalogFilters, Paginated, QuoteRow } from "@/types";

defineOptions({ layout: TenantLayout });

defineProps<{
    quotes: Paginated<QuoteRow>;
    filters: CatalogFilters;
}>();

const { permissions } = usePermission();
</script>

<template>
    <Head title="Orçamentos" />
    <div>
        <div class="mb-4 flex items-center justify-between border-b border-border pb-4">
            <h2 class="text-3xl font-bold tracking-tight text-foreground">Orçamentos</h2>

            <Button
                v-if="permissions.includes('documents.itbi_calculator.view')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.documents.fee-calculator.index')"><Calculator /> Nova calculadora</Link>
            </Button>
        </div>
        <ServerDataTable
            :columns="columns"
            :paginator="quotes"
            :filters="filters"
            :url="route('tenant.documents.quotes.list')"
            :with-trashed-filter="false"
            search-placeholder="Buscar por nome ou CPF..."
        />
    </div>
</template>
