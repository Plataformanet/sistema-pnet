<script setup lang="ts">
import { Button } from "@/components/ui/button";
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from "@/components/ui/sheet";
import { Check } from "lucide-vue-next";
import { computed } from "vue";
import type { FeeDiscountOption } from "@/types";

/**
 * Escolha de no máximo um desconto legal permitido para a UF. "Remover
 * seleção" realmente limpa o desconto (na origem só mudava o visual).
 */
const props = defineProps<{
    open: boolean;
    discounts: FeeDiscountOption[];
    state: string | null;
    modelValue: string | null;
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
    (e: "update:modelValue", value: string | null): void;
}>();

const available = computed(() =>
    props.discounts.filter((discount) => !discount.states || (props.state && discount.states.includes(props.state))),
);

function choose(value: string | null) {
    emit("update:modelValue", value);
    emit("update:open", false);
}
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent class="w-full overflow-y-auto sm:max-w-xl">
            <SheetHeader>
                <SheetTitle>Defina o desconto a ser utilizado</SheetTitle>
                <SheetDescription>O desconto é aplicado pela calculadora do Registro de Imóveis.</SheetDescription>
            </SheetHeader>

            <div class="space-y-3 px-4">
                <button
                    v-for="discount in available"
                    :key="discount.value"
                    type="button"
                    class="w-full rounded-md border p-4 text-left transition-colors hover:bg-muted/50"
                    :class="modelValue === discount.value ? 'border-primary bg-muted/40' : 'border-border'"
                    @click="choose(discount.value)"
                >
                    <p class="flex items-center gap-2 font-semibold">
                        <Check v-if="modelValue === discount.value" class="h-4 w-4 text-primary" />
                        {{ discount.label }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">{{ discount.legal_text }}</p>
                </button>

                <div class="flex justify-between gap-2 pb-4">
                    <Button type="button" variant="outline" :disabled="!modelValue" @click="choose(null)">Remover seleção</Button>
                    <Button type="button" variant="ghost" @click="emit('update:open', false)">Sair</Button>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
