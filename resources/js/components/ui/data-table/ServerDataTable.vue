<script setup lang="ts" generic="TData, TValue">
import type { ColumnDef } from "@tanstack/vue-table";
import { FlexRender, getCoreRowModel, useVueTable } from "@tanstack/vue-table";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { router } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";
import { ref } from "vue";
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from "lucide-vue-next";
import type { CatalogFilters, Paginated } from "@/types";

/**
 * Tabela com paginação, busca e filtro de excluídos feitos no servidor.
 * Mantém a mesma aparência do DataTable (paginação no cliente).
 */
const props = withDefaults(
    defineProps<{
        columns: ColumnDef<TData, TValue>[];
        paginator: Paginated<TData>;
        filters?: CatalogFilters;
        url: string;
        withTrashedFilter?: boolean;
        searchPlaceholder?: string;
        extraParams?: Record<string, unknown>;
        /** Habilita arrastar e soltar linhas; a linha só arrasta/recebe quando retorna true. */
        rowDraggable?: (row: TData) => boolean;
    }>(),
    {
        filters: () => ({}),
        withTrashedFilter: true,
        searchPlaceholder: "Pesquisar...",
        extraParams: () => ({}),
        rowDraggable: undefined,
    },
);

const emit = defineEmits<{
    /** Linha `source` solta sobre a linha `target`. */
    rowDrop: [source: TData, target: TData];
}>();

const draggingIndex = ref<number | null>(null);
const overIndex = ref<number | null>(null);

function canDrag(row: TData): boolean {
    return props.rowDraggable?.(row) ?? false;
}

function onDragStart(event: DragEvent, index: number, row: TData) {
    // Arrastar um link/imagem dentro de uma linha comum também dispara o evento no <tr>.
    if (!canDrag(row)) {
        return;
    }

    draggingIndex.value = index;
    event.dataTransfer?.setData("text/plain", String(index));

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = "move";
    }
}

function onDragOver(event: DragEvent, index: number, row: TData) {
    if (draggingIndex.value === null || !canDrag(row)) {
        return;
    }

    event.preventDefault();
    overIndex.value = index;
}

function onDrop(index: number) {
    const from = draggingIndex.value;
    const rows = props.paginator.data;

    if (from !== null && from !== index) {
        emit("rowDrop", rows[from], rows[index]);
    }

    onDragEnd();
}

function onDragEnd() {
    draggingIndex.value = null;
    overIndex.value = null;
}

/**
 * Indicador de onde a linha vai cair: acima do alvo ao subir, abaixo ao descer.
 */
function dropIndicator(index: number): string {
    if (draggingIndex.value === null || overIndex.value !== index || draggingIndex.value === index) {
        return draggingIndex.value === index ? "opacity-50" : "";
    }

    return draggingIndex.value > index
        ? "border-t-2 border-t-primary"
        : "border-b-2 border-b-primary";
}

const search = ref<string>(props.filters.search ?? "");
const trashed = ref<boolean>(
    props.filters.trashed === true || props.filters.trashed === "1",
);

const table = useVueTable({
    get data() {
        return props.paginator.data;
    },
    get columns() {
        return props.columns;
    },
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
});

function visit(page: number | null = null) {
    router.get(
        props.url,
        {
            ...props.extraParams,
            search: search.value || undefined,
            trashed: trashed.value ? 1 : undefined,
            page: page && page > 1 ? page : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const debouncedSearch = useDebounceFn(() => visit(), 400);

function onSearch(value: string | number) {
    search.value = String(value);
    debouncedSearch();
}

function onTrashed(value: boolean | "indeterminate") {
    trashed.value = value === true;
    visit();
}
</script>

<template>
    <div>
        <div class="flex flex-wrap items-center justify-between gap-4 py-4">
            <Input
                class="max-w-sm"
                :placeholder="searchPlaceholder"
                :model-value="search"
                @update:model-value="onSearch"
            />

            <slot name="toolbar" />

            <div v-if="withTrashedFilter" class="flex items-center gap-2">
                <Checkbox
                    id="show-trashed"
                    :model-value="trashed"
                    @update:model-value="onTrashed"
                />
                <Label for="show-trashed" class="cursor-pointer"
                    >Mostrar excluídos</Label
                >
            </div>
        </div>
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                    >
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                        >
                            <FlexRender
                                v-if="!header.isPlaceholder"
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="table.getRowModel().rows?.length">
                        <TableRow
                            v-for="(row, index) in table.getRowModel().rows"
                            :key="row.id"
                            :draggable="canDrag(row.original)"
                            :class="dropIndicator(index)"
                            @dragstart="onDragStart($event, index, row.original)"
                            @dragover="onDragOver($event, index, row.original)"
                            @drop.prevent="onDrop(index)"
                            @dragend="onDragEnd"
                        >
                            <TableCell
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                            >
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    :props="cell.getContext()"
                                />
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow>
                            <TableCell
                                :colspan="columns.length"
                                class="h-24 text-center"
                            >
                                Nenhum resultado encontrado.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>
        <div class="flex items-center justify-between px-2 pt-4">
            <div class="flex-1 text-sm text-muted-foreground">
                Total de registros: {{ paginator.total }}
            </div>
            <div class="flex items-center space-x-6 lg:space-x-8">
                <div
                    class="flex items-center justify-center text-sm font-medium"
                >
                    Página {{ paginator.current_page }} de
                    {{ Math.max(paginator.last_page, 1) }}
                </div>
                <div class="flex items-center space-x-2">
                    <Button
                        variant="outline"
                        class="hidden h-8 w-8 p-0 lg:flex"
                        :disabled="paginator.current_page <= 1"
                        @click="visit(1)"
                    >
                        <span class="sr-only">Ir para primeira página</span>
                        <ChevronsLeft class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        class="h-8 w-8 p-0"
                        :disabled="paginator.current_page <= 1"
                        @click="visit(paginator.current_page - 1)"
                    >
                        <span class="sr-only">Ir para página anterior</span>
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        class="h-8 w-8 p-0"
                        :disabled="paginator.current_page >= paginator.last_page"
                        @click="visit(paginator.current_page + 1)"
                    >
                        <span class="sr-only">Ir para próxima página</span>
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        class="hidden h-8 w-8 p-0 lg:flex"
                        :disabled="paginator.current_page >= paginator.last_page"
                        @click="visit(paginator.last_page)"
                    >
                        <span class="sr-only">Ir para última página</span>
                        <ChevronsRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
