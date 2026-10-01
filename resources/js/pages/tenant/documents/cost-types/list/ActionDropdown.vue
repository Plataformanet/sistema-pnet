<script setup lang="ts">
import { ref } from "vue";
import { MoreHorizontal, Pencil, Trash, RotateCcw } from "lucide-vue-next";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { Link, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { usePermission } from "@/composables/usePermission";
import type { CostType } from "@/types";

const props = defineProps<{
    costType: CostType;
}>();

const { permissions } = usePermission();

const showDeleteDialog = ref(false);

const deleteItem = () => {
    router.delete(route("tenant.documents.cost-types.destroy", props.costType.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
};

const restoreItem = () => {
    router.patch(
        route("tenant.documents.cost-types.restore", props.costType.id),
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <div>
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="ghost" class="h-8 w-8 p-0">
                    <span class="sr-only">Abrir menu</span>
                    <MoreHorizontal class="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuLabel>Ações</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    as-child
                    v-if="!costType.deleted_at && permissions.includes('documents.cost_types.edit')"
                >
                    <Link
                        :href="route('tenant.documents.cost-types.edit', costType.id)"
                        class="flex w-full cursor-pointer items-center"
                    >
                        <Pencil class="mr-2 h-4 w-4" /> Editar
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-if="costType.deleted_at && permissions.includes('documents.cost_types.delete')"
                    @click="restoreItem"
                >
                    <RotateCcw class="mr-2 h-4 w-4" /> Restaurar
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    v-if="!costType.deleted_at && permissions.includes('documents.cost_types.delete')"
                    @click="showDeleteDialog = true"
                    class="text-red-600"
                >
                    <Trash class="mr-2 h-4 w-4" /> Excluir
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>

        <AlertDialog
            :open="showDeleteDialog"
            @update:open="showDeleteDialog = $event"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle
                        >Tem certeza que deseja excluir
                        {{ costType.name }}?</AlertDialogTitle
                    >
                    <AlertDialogDescription>
                        O tipo de custo deixará de aparecer nos cadastros, mas
                        poderá ser restaurado depois. Propostas e orçamentos
                        que já o utilizam não serão afetados.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="showDeleteDialog = false"
                        >Cancelar</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="bg-red-600 text-white hover:bg-red-700"
                        @click="deleteItem"
                    >
                        Continuar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
