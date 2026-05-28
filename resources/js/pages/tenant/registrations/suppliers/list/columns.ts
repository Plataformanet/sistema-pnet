import { h } from "vue";
import { ArrowUpDown } from "lucide-vue-next";
import { ColumnDef } from "@tanstack/vue-table";
import { Button } from "@/components/ui/button";
import ActionDropdown from "./ActionDropdown.vue";
import { Supplier } from "@/types";

export const columns: ColumnDef<Supplier>[] = [
    {
        accessorKey: "contact.name_corporatereason",
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: "ghost",
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === "asc"),
                },
                () => [
                    "Nome/Razão Social",
                    h(ArrowUpDown, { class: "ml-2 h-4 w-4" }),
                ],
            );
        },
    },
    {
        id: "document",
        accessorFn: (row) => row.contact.cpf_cnpj,
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: "ghost",
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === "asc"),
                },
                () => ["CNPJ / CPF", h(ArrowUpDown, { class: "ml-2 h-4 w-4" })],
            );
        },
        cell: ({ row }) => {
            const cpf_cnpj = row.original.contact.cpf_cnpj;
            return cpf_cnpj ? cpf_cnpj : "-----";
        },
    },
    {
        accessorKey: "supply_category",
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
            return row.getValue("supply_category") ?? "-----";
        },
    },
    {
        accessorKey: "contact.email",
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: "ghost",
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === "asc"),
                },
                () => ["E-mail", h(ArrowUpDown, { class: "ml-2 h-4 w-4" })],
            );
        },
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const supplier = row.original;
            return h(
                "div",
                { class: "relative flex justify-end" },
                h(ActionDropdown, {
                    supplier,
                }),
            );
        },
    },
];
