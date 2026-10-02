<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";

defineProps<{
    open: boolean;
    title: string;
    description: string;
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
    (e: "confirm"): void;
}>();

/**
 * Confirma antes de fechar. O `AlertDialogAction` fecha o diálogo antes de
 * repassar o clique, e quem usa este componente limpa o item pendente ao
 * fechar: a confirmação chegava sem item e nada era enviado.
 */
function confirm() {
    emit("confirm");
    emit("update:open", false);
}
</script>

<template>
    <AlertDialog :open="open" @update:open="emit('update:open', $event)">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription>{{ description }}</AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="emit('update:open', false)">Cancelar</AlertDialogCancel>
                <Button type="button" class="bg-red-600 text-white hover:bg-red-700" @click="confirm">Continuar</Button>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
