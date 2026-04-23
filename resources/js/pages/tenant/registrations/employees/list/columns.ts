import { h } from "vue";
import { Employee } from "@/types";
import { ArrowUpDown } from 'lucide-vue-next';
import { ColumnDef } from "@tanstack/vue-table";
import { Button } from "@/components/ui/button";
import ActionDropdown from "./ActionDropdown.vue";
import { Checkbox } from "@/components/ui/checkbox";

export const columns: ColumnDef<Employee>[] = [
    {
        id: "select",
        header: ({ table }) => h(Checkbox, {
            "checked": table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && "indeterminate"),
            "onUpdate:checked": (value: boolean) => table.toggleAllPageRowsSelected(!!value),
            "ariaLabel": "Select all",
        }),
        cell: ({ row }) => h(Checkbox, {
            "checked": row.getIsSelected(),
            "onUpdate:checked": (value: boolean) => row.toggleSelected(!!value),
            "ariaLabel": "Select row",
        }),
        enableSorting: false,
        enableHiding: false,
    },
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
        id: "document",
        accessorFn: (row: any) => row.cpf,
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
            const cpf = row.original.cpf;
            return cpf ? cpf : "-----";
        },
    },
    {
        accessorKey: "position",
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: "ghost",
                    onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                },
                () => [
                    "Cargo",
                    h(ArrowUpDown, { class: "ml-2 h-4 w-4" }),
                ]
            )
        },
        cell: ({ row }) => {
            return row.getValue("position") ?? "-----";
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
            const employee = row.original;
            return h("div", { class: "relative flex justify-end" }, h(ActionDropdown, {
                employee,
            }));
        },
    },
];
