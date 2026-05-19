import { h } from "vue";
import { Client } from "@/types";
import { ArrowUpDown, ChevronDown } from 'lucide-vue-next'
import { ColumnDef } from "@tanstack/vue-table";
import { Button } from "@/components/ui/button";
import ActionDropdown from "./ActionDropdown.vue";

export const columns: ColumnDef<Client>[] = [
    {
        accessorKey: "contact.name_corporatereason",
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
        id: "document",
        accessorFn: (row) => row.contact?.cpf_cnpj,
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: "ghost",
                    onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                },
                () => [
                    "CPF / CNPJ",
                    h(ArrowUpDown, { class: "ml-2 h-4 w-4" }),
                ]
            )
        },
        cell: ({ row }) => {
            const cpf_cnpj = row.original.contact?.cpf_cnpj;
            return cpf_cnpj ? cpf_cnpj : "-----";
        },
    },
    {
        accessorKey: "contact.email",
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
