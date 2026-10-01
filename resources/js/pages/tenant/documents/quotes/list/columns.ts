import { h } from "vue";
import { ColumnDef } from "@tanstack/vue-table";
import { maskCPF } from "@/lib/masks";
import ActionDropdown from "./ActionDropdown.vue";
import type { QuoteRow } from "@/types";

const badgeClasses: Record<string, string> = {
    open: "bg-blue-100 text-blue-800",
    expired: "bg-red-100 text-red-800",
    converted: "bg-green-100 text-green-800",
};

export const columns: ColumnDef<QuoteRow>[] = [
    {
        accessorKey: "number",
        header: "Nº",
    },
    {
        accessorKey: "name",
        header: "Cliente",
    },
    {
        accessorKey: "cpf",
        header: "CPF",
        cell: ({ row }) => maskCPF(row.original.cpf),
    },
    {
        accessorKey: "valid_until",
        header: "Validade",
        cell: ({ row }) =>
            row.original.valid_until ? new Date(`${row.original.valid_until}T00:00:00`).toLocaleDateString("pt-BR") : "—",
    },
    {
        accessorKey: "status",
        header: "Status",
        cell: ({ row }) =>
            h(
                "span",
                {
                    class: `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${badgeClasses[row.original.status.value]}`,
                },
                row.original.status.label,
            ),
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const quote = row.original;
            return h("div", { class: "relative flex justify-end" }, h(ActionDropdown, { quote }));
        },
    },
];
