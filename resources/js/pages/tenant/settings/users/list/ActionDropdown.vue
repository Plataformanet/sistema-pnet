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
import type { User } from "@/types";

const props = defineProps<{
    user: User;
}>();

const { permissions } = usePermission();

const showDeleteDialog = ref(false);

const deleteItem = () => {
    if (props.user.id) {
        router.delete(route("tenant.settings.users.destroy", props.user.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteDialog.value = false;
            },
        });
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
                    v-if="permissions.includes('settings.users.view')"
                    @click="console.log('Visualizar', user.id)"
                >
                    <Eye class="mr-2 h-4 w-4" /> Visualizar
                </DropdownMenuItem>
                <DropdownMenuItem
                    as-child
                    v-if="permissions.includes('settings.users.edit')"
                >
                    <Link
                        :href="route('tenant.settings.users.edit', user.id)"
                        class="flex w-full cursor-pointer items-center"
                    >
                        <Pencil class="mr-2 h-4 w-4" /> Editar
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    v-if="permissions.includes('settings.users.delete')"
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
                        permanentemente o usuário e removerá os dados de nossos
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
