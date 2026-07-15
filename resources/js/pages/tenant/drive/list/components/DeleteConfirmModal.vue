<script setup lang="ts">
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
import type { Drive } from "@/types";

defineProps<{
    isOpen: boolean;
    item: Drive | null;
    isBulk: boolean;
    selectedCount: number;
}>();

const emit = defineEmits<{
    (e: "update:isOpen", val: boolean): void;
    (e: "confirm"): void;
}>();
</script>

<template>
    <AlertDialog
        :open="isOpen"
        @update:open="(val) => emit('update:isOpen', val)"
    >
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle class="text-slate-800 font-bold">
                    {{ isBulk ? 'Excluir Itens Selecionados?' : 'Mover para a Lixeira?' }}
                </AlertDialogTitle>
                <AlertDialogDescription class="text-slate-500 font-medium">
                    <span v-if="isBulk">
                        Tem certeza de que deseja mover os {{ selectedCount }} itens selecionados para a lixeira?
                    </span>
                    <span v-else>
                        Tem certeza de que deseja mover o item
                        <strong class="text-slate-700 font-semibold">"{{ item?.name }}"</strong>
                        para a lixeira? Você poderá restaurá-lo mais tarde.
                    </span>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel class="cursor-pointer text-slate-600">
                    Cancelar
                </AlertDialogCancel>
                <AlertDialogAction
                    @click="emit('confirm')"
                    class="bg-rose-600 hover:bg-rose-700 text-white cursor-pointer font-semibold"
                >
                    Excluir
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
