<script setup lang="ts">
import { ref } from "vue";
import { MoreHorizontal, Eye, Pencil, Trash } from "lucide-vue-next";
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
import { ServiceCategory } from "@/types";

const props = defineProps<{
    category: ServiceCategory;
}>();

const { permissions } = usePermission();

const showDeleteDialog = ref(false);

const deleteItem = () => {
    if (props.category.id) {
        router.delete(
            route("tenant.services.categories.destroy", props.category.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    showDeleteDialog.value = false;
                },
            },
        );
    }
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
                    v-if="permissions.includes('services.categories.view')"
                    @click="console.log('Visualizar', category.id)"
                >
                    <Eye class="mr-2 h-4 w-4" /> Visualizar
                </DropdownMenuItem>
                <DropdownMenuItem
                    as-child
                    v-if="permissions.includes('services.categories.edit')"
                >
                    <Link
                        :href="
                            route(
                                'tenant.services.categories.edit',
                                category.id,
                            )
                        "
                        class="flex w-full cursor-pointer items-center"
                    >
                        <Pencil class="mr-2 h-4 w-4" /> Editar
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    v-if="permissions.includes('services.categories.delete')"
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
                        >Você tem certeza absoluta?</AlertDialogTitle
                    >
                    <AlertDialogDescription>
                        Esta ação não pode ser desfeita. Isso excluirá
                        permanentemente a categoria de serviço e removerá os
                        dados de nossos servidores.
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
