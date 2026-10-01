<script setup lang="ts">
import { ref } from "vue";
import { Eye, FileText, MoreHorizontal, Pencil, Trash } from "lucide-vue-next";
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
import type { ProposalListRow } from "@/types";

const props = defineProps<{
    proposal: ProposalListRow;
}>();

const { permissions } = usePermission();

const showDeleteDialog = ref(false);

const deleteItem = () => {
    router.delete(route("tenant.documents.proposals.destroy", props.proposal.id), {
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
                <DropdownMenuItem as-child v-if="permissions.includes('documents.proposals.view')">
                    <Link :href="route('tenant.documents.proposals.show', proposal.id)" class="flex w-full cursor-pointer items-center">
                        <Eye class="mr-2 h-4 w-4" /> Visualizar
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem as-child v-if="permissions.includes('documents.proposals.view')">
                    <Link :href="route('tenant.documents.proposals.edit', proposal.id)" class="flex w-full cursor-pointer items-center">
                        <Pencil class="mr-2 h-4 w-4" />
                        {{ permissions.includes("documents.proposals.edit") ? "Editar" : "Abrir" }}
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                    <a :href="route('tenant.documents.proposals.pdf.info', proposal.id)" target="_blank" class="flex w-full cursor-pointer items-center">
                        <FileText class="mr-2 h-4 w-4" /> Informativo (PDF)
                    </a>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    v-if="permissions.includes('documents.proposals.delete')"
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
                    <AlertDialogTitle>Tem certeza que deseja excluir a proposta Nº {{ proposal.number }}?</AlertDialogTitle>
                    <AlertDialogDescription>
                        A proposta deixará de aparecer nas listagens. Os proponentes e seus acessos são mantidos.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="showDeleteDialog = false">Cancelar</AlertDialogCancel>
                    <AlertDialogAction class="bg-red-600 text-white hover:bg-red-700" @click="deleteItem">
                        Continuar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
