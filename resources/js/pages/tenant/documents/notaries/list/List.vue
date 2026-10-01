<script setup lang="ts">
import { ServerDataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/documents/notaries/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import type { Notary, CatalogFilters, Paginated, Option } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    notaries: Paginated<Notary>;
    filters: CatalogFilters;
    states?: Option[];
}>();

const { permissions } = usePermission();
</script>

<template>
    <Head title="Cartórios" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Cartórios
                </h2>
            </div>

            <Button
                v-if="permissions.includes('documents.notaries.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.documents.notaries.create')"
                    ><Plus /> Novo cartório</Link
                >
            </Button>
        </div>
        <ServerDataTable
            :columns="columns"
            :paginator="props.notaries"
            :filters="props.filters"
            :url="route('tenant.documents.notaries.list')"
        />
    </div>
</template>
