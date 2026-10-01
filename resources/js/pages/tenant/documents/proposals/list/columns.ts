import { h } from "vue";
import { ColumnDef } from "@tanstack/vue-table";
import { Checkbox } from "@/components/ui/checkbox";
import { formatMoney } from "@/lib/masks";
import ActionDropdown from "./ActionDropdown.vue";
import type { ProposalListRow } from "@/types";

const badgeColors: Record<string, string> = {
    blue: "bg-blue-100 text-blue-800",
    amber: "bg-amber-100 text-amber-800",
    violet: "bg-violet-100 text-violet-800",
    red: "bg-red-100 text-red-800",
    green: "bg-green-100 text-green-800",
    orange: "bg-orange-100 text-orange-800",
};

interface Selection {
    isSelected: (id: number) => boolean;
    toggle: (id: number, checked: boolean) => void;
}

export const buildColumns = (selection: Selection): ColumnDef<ProposalListRow>[] => [
    {
        id: "select",
        enableHiding: false,
        header: "",
        cell: ({ row }) =>
            h(Checkbox, {
                modelValue: selection.isSelected(row.original.id),
                "onUpdate:modelValue": (value: boolean | "indeterminate") =>
                    selection.toggle(row.original.id, value === true),
                "aria-label": "Selecionar proposta",
            }),
    },
    {
        accessorKey: "number",
        header: "Nº",
    },
    {
        accessorKey: "created_at",
        header: "Cadastro",
        cell: ({ row }) => new Date(row.original.created_at).toLocaleDateString("pt-BR"),
    },
    {
        accessorKey: "applicants",
        header: "Proponentes",
        cell: ({ row }) => row.original.applicants.join(", "),
    },
    {
        accessorKey: "creator",
        header: "Criador",
        cell: ({ row }) => row.original.creator ?? "—",
    },
    {
        accessorKey: "property_condition",
        header: "Imóvel",
    },
    {
        accessorKey: "purchase_value",
        header: "Valor de compra",
        cell: ({ row }) => formatMoney(row.original.purchase_value),
    },
    {
        accessorKey: "status",
        header: "Status",
        cell: ({ row }) =>
            h(
                "span",
                {
                    class: `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${badgeColors[row.original.status.color] ?? "bg-gray-100 text-gray-800"}`,
                },
                row.original.status.label,
            ),
    },
    {
        accessorKey: "current_stage",
        header: "Etapa atual",
        cell: ({ row }) =>
            row.original.current_stage ?? (row.original.status.value === "finished" ? "Finalizada" : "Não iniciada"),
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const proposal = row.original;
            return h(
                "div",
                { class: "relative flex justify-end" },
                h(ActionDropdown, {
                    proposal,
                }),
            );
        },
    },
];
