<script setup lang="ts">
import { ref } from "vue";
import { Eye, MoreHorizontal, Pencil, Trash } from "lucide-vue-next";
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
import type { QuoteRow } from "@/types";

const props = defineProps<{
    quote: QuoteRow;
}>();

const { permissions } = usePermission();

const showDeleteDialog = ref(false);

const deleteItem = () => {
    router.delete(route("tenant.documents.quotes.destroy", props.quote.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
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
                <DropdownMenuItem as-child v-if="permissions.includes('documents.quotes.view')">
                    <Link :href="route('tenant.documents.quotes.show', quote.id)" class="flex w-full cursor-pointer items-center">
                        <Eye class="mr-2 h-4 w-4" /> Visualizar
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem as-child v-if="quote.status.value === 'open' && permissions.includes('documents.quotes.edit')">
                    <Link :href="route('tenant.documents.quotes.edit', quote.id)" class="flex w-full cursor-pointer items-center">
                        <Pencil class="mr-2 h-4 w-4" /> Editar
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    v-if="quote.status.value !== 'converted' && permissions.includes('documents.quotes.delete')"
                    @click="showDeleteDialog = true"
                    class="text-red-600"
                >
                    <Trash class="mr-2 h-4 w-4" /> Excluir
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>

        <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Tem certeza que deseja excluir o orçamento Nº {{ quote.number }}?</AlertDialogTitle>
                    <AlertDialogDescription>O orçamento deixará de aparecer na listagem.</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="showDeleteDialog = false">Cancelar</AlertDialogCancel>
                    <AlertDialogAction class="bg-red-600 text-white hover:bg-red-700" @click="deleteItem">Continuar</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
