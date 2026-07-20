<script setup lang="ts">
import { computed, ref } from "vue";
import { MoreHorizontal, Eye, Pencil, Trash, Power, PowerOff } from "lucide-vue-next";
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
import { Supplier } from "@/types";
import { usePermission } from "@/composables/usePermission";

const props = defineProps<{
    supplier: Supplier;
}>();

const showDeleteDialog = ref(false);

const { permissions } = usePermission();

const isActive = computed(() => props.supplier.active ?? true);

const deleteItem = () => {
    if (props.supplier.contact?.id) {
        router.delete(route('tenant.registrations.suppliers.destroy', props.supplier.contact.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteDialog.value = false;
            }
        });
    }
};

const toggleActive = () => {
    if (props.supplier.contact?.id) {
        router.patch(route('tenant.registrations.suppliers.toggle-active', props.supplier.contact.id), {
            active: !isActive.value,
        }, {
            preserveScroll: true,
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
                    v-if="permissions.includes('registrations.suppliers.view')"
                    @click="console.log('Visualizar', supplier.contact.id)"
                >
                    <Eye class="mr-2 h-4 w-4" /> Visualizar
                </DropdownMenuItem>
                <DropdownMenuItem as-child
                    v-if="permissions.includes('registrations.suppliers.edit')"
                >
                    <Link
                        :href="
                            route(
                                'tenant.registrations.suppliers.edit',
                                supplier.contact.id,
                            )
                        "
                        class="flex w-full cursor-pointer items-center"
                    >
                        <Pencil class="mr-2 h-4 w-4" /> Editar
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-if="permissions.includes('registrations.suppliers.edit')"
                    @click="toggleActive"
                    class="cursor-pointer"
                >
                    <component :is="isActive ? PowerOff : Power" class="mr-2 h-4 w-4" />
                    {{ isActive ? "Inativar" : "Ativar" }}
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    v-if="permissions.includes('registrations.suppliers.delete')"
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
                    <AlertDialogTitle>Você tem certeza absoluta?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Esta ação não pode ser desfeita. Isso excluirá permanentemente o
                        fornecedor e removerá os dados de nossos servidores.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="showDeleteDialog = false">Cancelar</AlertDialogCancel>
                    <AlertDialogAction class="bg-red-600 hover:bg-red-700 text-white" @click="deleteItem">
                        Continuar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
