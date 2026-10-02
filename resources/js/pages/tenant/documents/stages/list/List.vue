<script setup lang="ts">
import { ServerDataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/documents/stages/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import { computed } from "vue";
import type { Stage, CatalogFilters, Paginated } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    stages: Paginated<Stage>;
    filters: CatalogFilters;
}>();

const { permissions } = usePermission();

const canReorder = computed(() => permissions.value.includes("documents.stages.edit"));
const tableColumns = computed(() => columns(canReorder.value));

function isDraggable(stage: Stage): boolean {
    return canReorder.value && !stage.deleted_at;
}

/**
 * Leva a etapa arrastada para a posição da etapa onde foi solta.
 */
function reorder(source: Stage, target: Stage) {
    router.patch(
        route("tenant.documents.stages.move", source.id),
        { target_id: target.id },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Etapas da Timeline" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Etapas da Timeline
                </h2>
            </div>

            <Button
                v-if="permissions.includes('documents.stages.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.documents.stages.create')"
                    ><Plus /> Nova etapa</Link
                >
            </Button>
        </div>
        <ServerDataTable
            :columns="tableColumns"
            :paginator="props.stages"
            :filters="props.filters"
            :url="route('tenant.documents.stages.list')"
            :row-draggable="isDraggable"
            @row-drop="reorder"
        />
    </div>
</template>
