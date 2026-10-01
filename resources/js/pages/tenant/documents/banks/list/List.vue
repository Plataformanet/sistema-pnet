<script setup lang="ts">
import { ServerDataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/documents/banks/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import type { Bank, CatalogFilters, Paginated } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    banks: Paginated<Bank>;
    filters: CatalogFilters;
}>();

const { permissions } = usePermission();
</script>

<template>
    <Head title="Bancos" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Bancos
                </h2>
            </div>

            <Button
                v-if="permissions.includes('documents.banks.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.documents.banks.create')"
                    ><Plus /> Novo banco</Link
                >
            </Button>
        </div>
        <ServerDataTable
            :columns="columns"
            :paginator="props.banks"
            :filters="props.filters"
            :url="route('tenant.documents.banks.list')"
        />
    </div>
</template>
