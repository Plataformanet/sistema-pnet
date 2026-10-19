<script setup lang="ts">
import { ServerDataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/documents/stages/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Field, FieldLabel } from "@/components/ui/field";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Plus } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import { computed } from "vue";
import type { Stage, CatalogFilters, Paginated, TimelineOption } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    stages: Paginated<Stage>;
    filters: CatalogFilters & { bank_id?: number | string | null };
    bankId: number | null;
    timelines: TimelineOption[];
}>();

/** O Select não aceita valor vazio: a timeline padrão usa este marcador. */
const DEFAULT_TIMELINE = "default";

const { permissions } = usePermission();

const canReorder = computed(() => permissions.value.includes("documents.stages.edit"));
const tableColumns = computed(() => columns(canReorder.value));

const currentTimeline = computed(
    () => props.timelines.find((timeline) => timeline.bank_id === props.bankId) ?? props.timelines[0],
);

const emptyText = computed(() =>
    props.bankId !== null && currentTimeline.value?.stages_count === 0
        ? "Este banco usa a timeline padrão. Adicione a primeira etapa para criar uma timeline personalizada."
        : "Nenhum resultado encontrado.",
);

/** Selo da opção: total da padrão, timeline personalizada ou banco que usa a padrão. */
function timelineBadge(timeline: TimelineOption): { text: string; variant: "secondary" | "default" | "outline" } {
    if (timeline.bank_id === null) {
        return { text: `${timeline.stages_count} etapas`, variant: "secondary" };
    }

    return timeline.stages_count > 0
        ? { text: `Personalizada (${timeline.stages_count} etapas)`, variant: "default" }
        : { text: "Usa a padrão", variant: "outline" };
}

function timelineValue(bankId: number | null): string {
    return bankId === null ? DEFAULT_TIMELINE : String(bankId);
}

function changeTimeline(value: unknown) {
    const bankId = value === DEFAULT_TIMELINE ? undefined : Number(value);

    router.get(
        route("tenant.documents.stages.list"),
        { ...props.filters, bank_id: bankId, page: undefined },
        { preserveState: true },
    );
}

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
    <Head :title="`Etapas da Timeline — ${currentTimeline?.name ?? ''}`" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Etapas da Timeline — {{ currentTimeline?.name }}
                </h2>
            </div>

            <Button
                v-if="permissions.includes('documents.stages.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.documents.stages.create', { bank_id: bankId ?? undefined })"
                    ><Plus /> Nova etapa</Link
                >
            </Button>
        </div>

        <Field class="mb-4 max-w-md">
            <FieldLabel for="timeline">Timeline</FieldLabel>
            <Select :model-value="timelineValue(bankId)" @update:model-value="changeTimeline">
                <SelectTrigger id="timeline">
                    <SelectValue placeholder="Selecione a timeline...">
                        <span v-if="currentTimeline" class="flex items-center gap-2">
                            {{ currentTimeline.name }}
                            <Badge :variant="timelineBadge(currentTimeline).variant">
                                {{ timelineBadge(currentTimeline).text }}
                            </Badge>
                        </span>
                    </SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem
                            v-for="timeline in timelines"
                            :key="timelineValue(timeline.bank_id)"
                            :value="timelineValue(timeline.bank_id)"
                        >
                            <span class="flex items-center gap-2">
                                {{ timeline.name }}
                                <Badge :variant="timelineBadge(timeline).variant">{{ timelineBadge(timeline).text }}</Badge>
                            </span>
                        </SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
        </Field>

        <ServerDataTable
            :columns="tableColumns"
            :paginator="props.stages"
            :filters="props.filters"
            :url="route('tenant.documents.stages.list')"
            :extra-params="{ bank_id: bankId ?? undefined }"
            :empty-text="emptyText"
            :row-draggable="isDraggable"
            @row-drop="reorder"
        />
    </div>
</template>
