import { h } from "vue";
import { Client } from "./List.vue";
import { ArrowUpDown, ChevronDown } from 'lucide-vue-next'
import { ColumnDef } from "@tanstack/vue-table";
import { Button } from "@/components/ui/button";
import ActionDropdown from "./ActionDropdown.vue";

export const columns: ColumnDef<Client>[] = [
    {
        accessorKey: "name",
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: "ghost",
                    onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                },
                () => [
                    "Nome",
                    h(ArrowUpDown, { class: "ml-2 h-4 w-4" }),
                ]
            )
        },
    },
    {
        accessorKey: "cpf",
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: "ghost",
                    onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                },
                () => [
                    "CPF",
                    h(ArrowUpDown, { class: "ml-2 h-4 w-4" }),
                ]
            )
        },
        cell: ({ row }) => {
            return row.getValue("cpf") ?? "-----";
        },
    },
    {
        accessorKey: "cnpj",
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: "ghost",
                    onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                },
                () => [
                    "CNPJ",
                    h(ArrowUpDown, { class: "ml-2 h-4 w-4" }),
                ]
            )
        },
        cell: ({ row }) => {
            return row.getValue("cnpj") ?? "-----";
        },
    },
    {
        accessorKey: "email",
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: "ghost",
                    onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                },
                () => [
                    "E-mail",
                    h(ArrowUpDown, { class: "ml-2 h-4 w-4" }),
                ]
            )
        },
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const client = row.original;
            return h("div", { class: "relative flex justify-end" }, h(ActionDropdown, {
                client,
            }));
        },
    },
];
