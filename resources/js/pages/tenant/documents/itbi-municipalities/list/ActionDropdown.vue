<script setup lang="ts">
import { ref } from "vue";
import { MoreHorizontal, Pencil, Percent, Trash } from "lucide-vue-next";
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
import type { ItbiMunicipalityRow } from "@/types";

const props = defineProps<{
    municipality: ItbiMunicipalityRow;
}>();

const { permissions } = usePermission();

const showDeleteDialog = ref(false);

const deleteItem = () => {
    router.delete(route("tenant.documents.itbi-municipalities.destroy", props.municipality.id), {
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
                <template v-if="permissions.includes('documents.itbi_municipalities.edit')">
                    <DropdownMenuItem as-child>
                        <Link :href="route('tenant.documents.itbi-municipalities.edit', municipality.id)" class="flex w-full cursor-pointer items-center">
                            <Pencil class="mr-2 h-4 w-4" /> Editar município
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem as-child>
                        <Link
                            :href="
                                municipality.module.value === 'value_brackets'
                                    ? route('tenant.documents.itbi-municipalities.brackets.edit', municipality.id)
                                    : route('tenant.documents.itbi-municipalities.rates.edit', municipality.id)
                            "
                            class="flex w-full cursor-pointer items-center"
                        >
                            <Percent class="mr-2 h-4 w-4" /> {{ municipality.module.value === "value_brackets" ? "Faixas" : "Alíquotas" }}
                        </Link>
                    </DropdownMenuItem>
                </template>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    v-if="permissions.includes('documents.itbi_municipalities.delete')"
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
                    <AlertDialogTitle>Tem certeza que deseja excluir {{ municipality.name }}?</AlertDialogTitle>
                    <AlertDialogDescription>
                        As alíquotas e faixas do município serão apagadas e cálculos para ele deixarão de calcular o ITBI.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="showDeleteDialog = false">Cancelar</AlertDialogCancel>
                    <AlertDialogAction class="bg-red-600 text-white hover:bg-red-700" @click="deleteItem">Continuar</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
