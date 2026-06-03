import { h } from "vue";
import { ArrowUpDown } from "lucide-vue-next";
import { ColumnDef } from "@tanstack/vue-table";
import { Button } from "@/components/ui/button";
import ActionDropdown from "./ActionDropdown.vue";
import { FinanceSubcategory } from "@/types";

export const columns: ColumnDef<FinanceSubcategory>[] = [
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
                    "Subcategoria",
                    h(ArrowUpDown, { class: "ml-2 h-4 w-4" }),
                ],
            );
        },
    },
    {
        accessorKey: "financial_category.name",
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: "ghost",
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === "asc"),
                },
                () => ["Categoria", h(ArrowUpDown, { class: "ml-2 h-4 w-4" })],
            );
        },
        cell: ({ row }) => {
            return row.original.financial_category?.name || "-";
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
            const subcategory = row.original;
            return h(
                "div",
                { class: "relative flex justify-end" },
                h(ActionDropdown, {
                    subcategory,
                }),
            );
        },
    },
];
