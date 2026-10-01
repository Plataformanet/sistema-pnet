<script setup lang="ts">
import { ServerDataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { buildColumns } from "@/pages/tenant/documents/proposals/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { FileText, Plus, Printer } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import { computed, ref } from "vue";
import type { Paginated, ProposalListRow, StatusOption } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    proposals: Paginated<ProposalListRow>;
    statusCounts: Record<string, number>;
    statuses: StatusOption[];
    filters: { status?: string | null; search?: string | null };
}>();

const { permissions } = usePermission();

const selected = ref<number[]>([]);
const total = computed(() => Object.values(props.statusCounts).reduce((sum, count) => sum + count, 0));

const columns = computed(() =>
    buildColumns({
        isSelected: (id: number) => selected.value.includes(id),
        toggle: (id: number, checked: boolean) => {
            selected.value = checked ? [...selected.value, id] : selected.value.filter((item) => item !== id);
        },
    }),
);

function filterByStatus(status: string | null) {
    selected.value = [];
    router.get(
        route("tenant.documents.proposals.list"),
        { status: status ?? undefined, search: props.filters.search ?? undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const listPdfUrl = computed(() =>
    route("tenant.documents.proposals.pdf.list", {
        status: props.filters.status ?? undefined,
        search: props.filters.search ?? undefined,
    }),
);

const selectionPdfUrl = computed(() => route("tenant.documents.proposals.pdf.list", { ids: selected.value }));
</script>

<template>
    <Head title="Propostas" />
    <div>
        <div class="mb-4 flex items-center justify-between border-b border-border pb-4">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">Propostas</h2>
            </div>

            <Button
                v-if="permissions.includes('documents.proposals.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.documents.proposals.create')"><Plus /> Nova proposta</Link>
            </Button>
        </div>

        <div class="mb-2 flex flex-wrap gap-2">
            <Button :variant="!filters.status ? 'default' : 'outline'" size="sm" @click="filterByStatus(null)">
                Todas ({{ total }})
            </Button>
            <Button
                v-for="status in statuses"
                :key="status.value"
                :variant="filters.status === status.value ? 'default' : 'outline'"
                size="sm"
                @click="filterByStatus(status.value)"
            >
                {{ status.label }} ({{ statusCounts[status.value] ?? 0 }})
            </Button>
        </div>

        <ServerDataTable
            :columns="columns"
            :paginator="proposals"
            :filters="filters"
            :url="route('tenant.documents.proposals.list')"
            :extra-params="{ status: filters.status ?? undefined }"
            :with-trashed-filter="false"
            search-placeholder="Buscar por nº, proponente ou CPF..."
        >
            <template #toolbar>
                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <a :href="listPdfUrl" target="_blank"><Printer class="mr-1 h-4 w-4" /> Imprimir lista</a>
                    </Button>
                    <Button v-if="selected.length" variant="outline" size="sm" as-child>
                        <a :href="selectionPdfUrl" target="_blank">
                            <FileText class="mr-1 h-4 w-4" /> Imprimir selecionadas ({{ selected.length }})
                        </a>
                    </Button>
                </div>
            </template>
        </ServerDataTable>
    </div>
</template>
