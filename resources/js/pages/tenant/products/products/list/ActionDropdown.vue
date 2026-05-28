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
import { Product } from "@/types";

const props = defineProps<{
    product: Product;
}>();

const { permissions } = usePermission();

const showDeleteDialog = ref(false);

const deleteItem = () => {
    if (props.product.id) {
        router.delete(
            route("tenant.products.products.delete", props.product.id),
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
                    v-if="permissions.includes('products.products.view')"
                    @click="console.log('Visualizar', product.id)"
                >
                    <Eye class="mr-2 h-4 w-4" /> Visualizar
                </DropdownMenuItem>
                <DropdownMenuItem
                    as-child
                    v-if="permissions.includes('products.products.edit')"
                >
                    <Link
                        :href="
                            route('tenant.products.products.edit', product.id)
                        "
                        class="flex w-full cursor-pointer items-center"
                    >
                        <Pencil class="mr-2 h-4 w-4" /> Editar
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    v-if="permissions.includes('products.products.delete')"
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
                        permanentemente o produto e removerá os dados de nossos
                        servidores.
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
