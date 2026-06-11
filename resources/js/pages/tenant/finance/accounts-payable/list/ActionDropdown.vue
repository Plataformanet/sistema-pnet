<script setup lang="ts">
import { MoreHorizontal, Eye, Check, Pencil, Trash } from "lucide-vue-next";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Button } from "@/components/ui/button";
import { Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { usePermission } from "@/composables/usePermission";

interface InstallmentRow {
    id: string | number;
    installment_id: string | number | null;
    status: string;
    [key: string]: any;
}

const props = defineProps<{
    item: InstallmentRow;
}>();

const emit = defineEmits<{
    (e: "pay", installmentId: string | number): void;
    (e: "delete", id: string | number): void;
}>();

const { permissions } = usePermission();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" class="h-8 w-8 p-0 cursor-pointer">
                <span class="sr-only">Abrir menu</span>
                <MoreHorizontal class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuLabel>Ações</DropdownMenuLabel>
            <DropdownMenuSeparator />

            <!-- Show detail -->
            <DropdownMenuItem
                v-if="permissions.includes('finance.accounts_payable.view')"
                as-child
            >
                <Link
                    :href="route('tenant.finance.accounts-payable.show', props.item.id)"
                    class="flex w-full cursor-pointer items-center"
                >
                    <Eye class="mr-2 h-4 w-4" /> Visualizar Detalhes
                </Link>
            </DropdownMenuItem>

            <!-- Liquidate (Pay) -->
            <DropdownMenuItem
                v-if="
                    permissions.includes('finance.accounts_payable.edit') &&
                    props.item.status !== 'paid' &&
                    props.item.installment_id
                "
                @click="emit('pay', props.item.installment_id)"
                class="text-emerald-600 focus:text-emerald-600 cursor-pointer"
            >
                <Check class="mr-2 h-4 w-4" /> Liquidar Parcela
            </DropdownMenuItem>

            <!-- Edit -->
            <DropdownMenuItem
                v-slot="{}"
                v-if="permissions.includes('finance.accounts_payable.edit')"
                as-child
            >
                <Link
                    :href="route('tenant.finance.accounts-payable.edit', props.item.id)"
                    class="flex w-full cursor-pointer items-center"
                >
                    <Pencil class="mr-2 h-4 w-4" /> Editar Lançamento
                </Link>
            </DropdownMenuItem>

            <DropdownMenuSeparator
                v-if="
                    permissions.includes('finance.accounts_payable.delete') &&
                    (permissions.includes('finance.accounts_payable.view') ||
                        permissions.includes('finance.accounts_payable.edit'))
                "
            />

            <!-- Delete -->
            <DropdownMenuItem
                v-if="permissions.includes('finance.accounts_payable.delete')"
                @click="emit('delete', props.item.id)"
                class="text-destructive focus:text-destructive cursor-pointer"
            >
                <Trash class="mr-2 h-4 w-4" /> Excluir Lançamento
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
