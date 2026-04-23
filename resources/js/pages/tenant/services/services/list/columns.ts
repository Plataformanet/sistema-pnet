import { h } from "vue";
import { Service } from "./List.vue";
import { maskCurrency } from "@/lib/masks";
import { ArrowUpDown } from 'lucide-vue-next'
import { ColumnDef } from "@tanstack/vue-table";
import { Button } from "@/components/ui/button";
import ActionDropdown from "./ActionDropdown.vue";

export const columns: ColumnDef<Service>[] = [
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
                    "Nome do Serviço",
                    h(ArrowUpDown, { class: "ml-2 h-4 w-4" }),
                ]
            )
        },
    },
    {
        accessorKey: "sku",
        header: "Código/SKU",
    },
    {
        accessorKey: "sell_value",
        header: "Valor de Venda",
        cell: ({ row }) => {
            const val = row.original.sell_value;
            return val !== undefined ? maskCurrency(String(val)) : "R$ 0,00";
        }
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
                        : "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-red-100 text-red-800"
                },
                isActive ? "Ativo" : "Inativo"
            );
        }
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row }) => {
            const service = row.original;
            return h("div", { class: "relative flex justify-end" }, h(ActionDropdown, {
                service,
            }));
        },
    },
];
