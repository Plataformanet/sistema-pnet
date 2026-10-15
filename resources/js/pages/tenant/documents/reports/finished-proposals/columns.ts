import { h } from "vue";
import { ColumnDef } from "@tanstack/vue-table";
import { Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { Checkbox } from "@/components/ui/checkbox";
import { Download, Eye } from "lucide-vue-next";
import type { FinishedProposalRow } from "@/types";

interface Selection {
    isSelected: (id: number) => boolean;
    toggle: (id: number, checked: boolean) => void;
    allOnPage: () => boolean | "indeterminate";
    toggleAllOnPage: (checked: boolean) => void;
}

const formatDate = (date: string | null) => (date ? new Date(`${date}T00:00:00`).toLocaleDateString("pt-BR") : "—");

export const buildColumns = (selection: Selection): ColumnDef<FinishedProposalRow>[] => [
    {
        id: "select",
        enableHiding: false,
        header: () =>
            h(Checkbox, {
                modelValue: selection.allOnPage(),
                "onUpdate:modelValue": (value: boolean | "indeterminate") => selection.toggleAllOnPage(value === true),
                "aria-label": "Selecionar todos desta página",
            }),
        cell: ({ row }) =>
            row.original.document
                ? h(Checkbox, {
                      modelValue: selection.isSelected(row.original.id),
                      "onUpdate:modelValue": (value: boolean | "indeterminate") =>
                          selection.toggle(row.original.id, value === true),
                      "aria-label": "Selecionar proposta",
                  })
                : null,
    },
    {
        accessorKey: "number",
        header: "Nº",
    },
    {
        accessorKey: "creator",
        header: "Criado por",
        cell: ({ row }) => row.original.creator ?? "—",
    },
    {
        accessorKey: "applicants",
        header: "Proponentes",
        cell: ({ row }) => row.original.applicants.join(", ") || "—",
    },
    {
        accessorKey: "development",
        header: "Empreendimento",
        cell: ({ row }) => row.original.development ?? "—",
    },
    {
        id: "document",
        header: "Documento",
        cell: ({ row }) => {
            const document = row.original.document;

            if (!document) {
                return h("span", { class: "text-muted-foreground" }, "Sem documento");
            }

            return h(
                "a",
                {
                    href: route("tenant.documents.proposals.documents.download", [row.original.id, document.id]),
                    class: "inline-flex items-center gap-1 text-primary underline",
                },
                [h(Download, { class: "h-4 w-4" }), document.title],
            );
        },
    },
    {
        accessorKey: "report_date",
        header: "Data de finalização",
        cell: ({ row }) => formatDate(row.original.report_date),
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) =>
            h(
                "div",
                { class: "flex justify-end" },
                h(
                    Link,
                    {
                        href: route("tenant.documents.proposals.show", row.original.id),
                        class: "inline-flex items-center gap-1 text-sm text-primary hover:underline",
                    },
                    () => [h(Eye, { class: "h-4 w-4" }), "Ver proposta"],
                ),
            ),
    },
];
