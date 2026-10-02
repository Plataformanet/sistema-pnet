import { h } from "vue";
import { ColumnDef } from "@tanstack/vue-table";
import { GripVertical } from "lucide-vue-next";
import ActionDropdown from "./ActionDropdown.vue";
import type { Stage } from "@/types";

/**
 * @param canReorder se o usuário pode arrastar as etapas (permissão de edição).
 */
export const columns = (canReorder: boolean): ColumnDef<Stage>[] => [
    {
        id: "drag",
        enableHiding: false,
        header: "",
        cell: ({ row }) =>
            canReorder && !row.original.deleted_at
                ? h(GripVertical, {
                      class: "h-4 w-4 cursor-grab text-muted-foreground active:cursor-grabbing",
                      "aria-label": "Arraste para reordenar",
                  })
                : null,
    },
    {
        accessorKey: "order",
        header: "Ordem",
    },
    {
        accessorKey: "name",
        header: "Nome",
    },
    {
        accessorKey: "has_date",
        header: "Data",
        cell: ({ row }) =>
            row.original.has_date
                ? row.original.date_required
                    ? "Sim (obrigatória)"
                    : "Sim"
                : "Não",
    },
    {
        accessorKey: "has_upload",
        header: "Upload",
        cell: ({ row }) =>
            row.original.has_upload
                ? row.original.upload_required
                    ? "Sim (obrigatória)"
                    : "Sim"
                : "Não",
    },
    {
        accessorKey: "completion_deadline_hours",
        header: "Conclusão (h)",
    },
    {
        accessorKey: "alert_deadline_hours",
        header: "Alerta (h)",
    },
    {
        accessorKey: "in_use_count",
        header: "Em uso",
        cell: ({ row }) => row.original.in_use_count ?? 0,
    },
    {
        accessorKey: "deleted_at",
        header: "Status",
        cell: ({ row }) =>
            h(
                "span",
                {
                    class: row.original.deleted_at
                        ? "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-red-100 text-red-800"
                        : "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-100 text-green-800",
                },
                row.original.deleted_at ? "Excluído" : "Ativo",
            ),
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const stage = row.original;
            return h(
                "div",
                { class: "relative flex justify-end" },
                h(ActionDropdown, {
                    stage,
                }),
            );
        },
    },
];
