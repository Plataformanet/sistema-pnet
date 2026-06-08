import { h } from "vue";
import { ArrowUpDown } from "lucide-vue-next";
import { ColumnDef } from "@tanstack/vue-table";
import { Button } from "@/components/ui/button";
import ActionDropdown from "./ActionDropdown.vue";
import { FinanceCategory } from "@/types";

export const columns: ColumnDef<FinanceCategory>[] = [
    {
        accessorKey: "name",
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: "ghost",
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === "asc"),
                },
                () => [
                    "Nome da Categoria",
                    h(ArrowUpDown, { class: "ml-2 h-4 w-4" }),
                ],
            );
        },
    },
    {
        accessorKey: "type",
        header: "Tipo",
        cell: ({ row }) => {
            const type = row.original.type;
            const isDespesa = type === "despesa";
            return h(
                "span",
                {
                    class: isDespesa
                        ? "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-red-100 text-red-800"
                        : "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-blue-100 text-blue-800",
                },
                isDespesa ? "Saída / Despesa" : "Entrada / Receita",
            );
        },
    },
    {
        accessorKey: "active",
        header: "Status",
        cell: ({ row }) => {
            const isActive = row.original.active;
            return h(
                "span",
                {
                    class: isActive
                        ? "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-100 text-green-800"
                        : "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-800",
                },
                isActive ? "Ativa" : "Inativa",
            );
        },
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const category = row.original;
            return h(
                "div",
                { class: "relative flex justify-end" },
                h(ActionDropdown, {
                    category,
                }),
            );
        },
    },
];
