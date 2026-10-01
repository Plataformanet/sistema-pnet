import { h } from "vue";
import { ColumnDef } from "@tanstack/vue-table";
import ActionDropdown from "./ActionDropdown.vue";
import type { ItbiMunicipalityRow } from "@/types";

export const columns: ColumnDef<ItbiMunicipalityRow>[] = [
    {
        accessorKey: "name",
        header: "Município",
    },
    {
        accessorKey: "state",
        header: "UF",
    },
    {
        accessorKey: "ibge_code",
        header: "Código IBGE",
    },
    {
        accessorKey: "module",
        header: "Módulo",
        cell: ({ row }) => row.original.module.label,
    },
    {
        accessorKey: "configured",
        header: "Parâmetros",
        cell: ({ row }) =>
            h(
                "span",
                {
                    class: row.original.configured
                        ? "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-100 text-green-800"
                        : "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-amber-100 text-amber-800",
                },
                row.original.configured ? "Configurado" : "Pendente",
            ),
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const municipality = row.original;
            return h("div", { class: "relative flex justify-end" }, h(ActionDropdown, { municipality }));
        },
    },
];
