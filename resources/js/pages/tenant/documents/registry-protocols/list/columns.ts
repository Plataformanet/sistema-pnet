import { h } from "vue";
import { ColumnDef } from "@tanstack/vue-table";
import { Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import ActionDropdown from "./ActionDropdown.vue";
import PositionBadge from "../components/PositionBadge.vue";
import type { RegistryProtocolRow } from "@/types";

const formatDate = (date: string | null) => (date ? new Date(`${date}T00:00:00`).toLocaleDateString("pt-BR") : "—");

export const columns: ColumnDef<RegistryProtocolRow>[] = [
    {
        id: "proposal",
        header: "Proposta",
        cell: ({ row }) => {
            const proposal = row.original.proposal;

            if (!proposal) {
                return "—";
            }

            if (proposal.deleted) {
                return h("span", { class: "text-muted-foreground" }, `Nº ${proposal.number} (excluída)`);
            }

            return h(
                Link,
                { href: route("tenant.documents.proposals.show", proposal.id), class: "text-primary underline" },
                () => `Nº ${proposal.number}`,
            );
        },
    },
    {
        accessorKey: "protocol_number",
        header: "Protocolo",
    },
    {
        accessorKey: "buyer_name",
        header: "Comprador",
    },
    {
        accessorKey: "seller_name",
        header: "Vendedor",
        cell: ({ row }) => row.original.seller_name || "—",
    },
    {
        accessorKey: "entered_at",
        header: "Entrada",
        cell: ({ row }) => formatDate(row.original.entered_at),
    },
    {
        accessorKey: "exited_at",
        header: "Saída",
        cell: ({ row }) => formatDate(row.original.exited_at),
    },
    {
        id: "position",
        header: "Posição",
        cell: ({ row }) => h(PositionBadge, { position: row.original.position }),
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) =>
            h("div", { class: "relative flex justify-end" }, h(ActionDropdown, { protocol: row.original })),
    },
];
