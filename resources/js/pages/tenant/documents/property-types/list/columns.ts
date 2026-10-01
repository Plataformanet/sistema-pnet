import { h } from "vue";
import { ArrowUpDown } from "lucide-vue-next";
import { ColumnDef } from "@tanstack/vue-table";
import { Button } from "@/components/ui/button";
import ActionDropdown from "./ActionDropdown.vue";
import type { PropertyType } from "@/types";

const yesNoBadge = (value: boolean) =>
    h(
        "span",
        {
            class: value
                ? "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-100 text-green-800"
                : "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-800",
        },
        value ? "Sim" : "Não",
    );

export const columns: ColumnDef<PropertyType>[] = [
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
                () => ["Nome", h(ArrowUpDown, { class: "ml-2 h-4 w-4" })],
            );
        },
    },
    {
        accessorKey: "requires_development",
        header: "Empreendimento",
        cell: ({ row }) => yesNoBadge(row.original.requires_development),
    },
    {
        accessorKey: "shows_unit",
        header: "Unidade",
        cell: ({ row }) => yesNoBadge(row.original.shows_unit),
    },
    {
        accessorKey: "shows_block",
        header: "Bloco",
        cell: ({ row }) => yesNoBadge(row.original.shows_block),
    },
    {
        accessorKey: "shows_number",
        header: "Número",
        cell: ({ row }) => yesNoBadge(row.original.shows_number),
    },
    {
        accessorKey: "shows_complement",
        header: "Complemento",
        cell: ({ row }) => yesNoBadge(row.original.shows_complement),
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
            const propertyType = row.original;
            return h(
                "div",
                { class: "relative flex justify-end" },
                h(ActionDropdown, {
                    propertyType,
                }),
            );
        },
    },
];
