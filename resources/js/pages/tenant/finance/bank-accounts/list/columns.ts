import { h } from "vue";
import { ArrowUpDown } from "lucide-vue-next";
import { ColumnDef } from "@tanstack/vue-table";
import { Button } from "@/components/ui/button";
import { maskCurrency } from "@/lib/masks";
import ActionDropdown from "./ActionDropdown.vue";
import { BankAccount } from "@/types";

export const columns: ColumnDef<BankAccount>[] = [
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
                    "Nome da Conta",
                    h(ArrowUpDown, { class: "ml-2 h-4 w-4" }),
                ],
            );
        },
    },
    {
        accessorKey: "bank",
        header: "Banco",
    },
    {
        accessorKey: "agency",
        header: "Agência",
    },
    {
        accessorKey: "account_number",
        header: "Conta",
    },
    {
        accessorKey: "account_type",
        header: "Tipo",
    },
    {
        accessorKey: "current_balance",
        header: "Saldo Atual",
        cell: ({ row }) => {
            const val = row.original.current_balance;
            return val !== undefined && val !== null
                ? maskCurrency(String(val))
                : "R$ 0,00";
        },
    },
    {
        accessorKey: "main_account",
        header: "Principal?",
        cell: ({ row }) => {
            const isMain = row.original.main_account;
            return h(
                "span",
                {
                    class: isMain
                        ? "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-indigo-100 text-indigo-800"
                        : "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-800",
                },
                isMain ? "Principal" : "Secundária",
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
            const bankAccount = row.original;
            return h(
                "div",
                { class: "relative flex justify-end" },
                h(ActionDropdown, {
                    bankAccount,
                }),
            );
        },
    },
];
