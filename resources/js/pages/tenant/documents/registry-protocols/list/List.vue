<script setup lang="ts">
import { ServerDataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/documents/registry-protocols/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Plus, X } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import { computed } from "vue";
import type {
    Paginated,
    RegistryProtocolFilters,
    RegistryProtocolPositionOption,
    RegistryProtocolRow,
    RegistryProtocolTab,
} from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    protocols: Paginated<RegistryProtocolRow>;
    tabCounts: Record<RegistryProtocolTab, number>;
    positions: RegistryProtocolPositionOption[];
    filters: RegistryProtocolFilters;
}>();

const { permissions } = usePermission();

const tabs: Array<{ key: RegistryProtocolTab; label: string }> = [
    { key: "open", label: "Em aberto" },
    { key: "closed", label: "Encerrados" },
    { key: "all", label: "Todos" },
];

const currentTab = computed<RegistryProtocolTab>(() => props.filters.status ?? "open");

/** Filtros da página repassados pelo ServerDataTable na busca e na paginação. */
const extraParams = computed(() => ({
    status: currentTab.value === "open" ? undefined : currentTab.value,
    position: props.filters.position ?? undefined,
    proposal_id: props.filters.proposal_id ?? undefined,
}));

function visit(params: Record<string, unknown>) {
    router.get(
        route("tenant.documents.registry-protocols.list"),
        { ...extraParams.value, search: props.filters.search ?? undefined, ...params },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function selectTab(tab: RegistryProtocolTab) {
    visit({ status: tab === "open" ? undefined : tab });
}

function selectPosition(value: unknown) {
    visit({ position: value === "all" ? undefined : value });
}
</script>

<template>
    <Head title="Protocolos de Registro" />
    <div>
        <div class="mb-4 flex items-center justify-between border-b border-border pb-4">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">Protocolos de Registro</h2>
            </div>

            <Button
                v-if="permissions.includes('documents.registry_protocols.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.documents.registry-protocols.create')"><Plus /> Novo protocolo</Link>
            </Button>
        </div>

        <div class="mb-2 flex flex-wrap gap-2">
            <Button
                v-for="tab in tabs"
                :key="tab.key"
                :variant="currentTab === tab.key ? 'default' : 'outline'"
                size="sm"
                @click="selectTab(tab.key)"
            >
                {{ tab.label }} ({{ tabCounts[tab.key] ?? 0 }})
            </Button>
        </div>

        <div
            v-if="filters.proposal_id"
            class="mb-2 inline-flex items-center gap-2 rounded-md border border-border bg-muted/40 px-3 py-1 text-sm"
        >
            Protocolos da proposta Nº {{ String(filters.proposal_id).padStart(5, "0") }}
            <button type="button" class="text-muted-foreground hover:text-foreground" @click="visit({ proposal_id: undefined })">
                <span class="sr-only">Remover filtro de proposta</span>
                <X class="h-4 w-4" />
            </button>
        </div>

        <ServerDataTable
            :columns="columns"
            :paginator="protocols"
            :filters="filters"
            :url="route('tenant.documents.registry-protocols.list')"
            :extra-params="extraParams"
            :with-trashed-filter="false"
            search-placeholder="Buscar por protocolo, comprador, vendedor ou nº da proposta..."
            empty-text="Nenhum protocolo encontrado."
        >
            <template #toolbar>
                <div class="w-full sm:w-56">
                    <Select
                        :model-value="filters.position ? String(filters.position) : 'all'"
                        @update:model-value="selectPosition"
                    >
                        <SelectTrigger aria-label="Filtrar por posição">
                            <SelectValue placeholder="Todas as posições" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="all">Todas as posições</SelectItem>
                                <SelectItem
                                    v-for="position in positions"
                                    :key="position.value"
                                    :value="String(position.value)"
                                >
                                    {{ position.label }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
            </template>
        </ServerDataTable>
    </div>
</template>
