<script setup lang="ts">
import { Input } from "@/components/ui/input";
import { centsToMask, maskCurrency, parseCurrencyToCents } from "@/lib/masks";
import { ref, watch } from "vue";

/**
 * Input monetário com máscara pt-BR. O v-model é sempre em centavos inteiros
 * (R$ 1.234,56 → 123456), que é o formato aceito pelo backend.
 */
const props = withDefaults(
    defineProps<{
        modelValue?: number | null;
        id?: string;
        placeholder?: string;
        disabled?: boolean;
        required?: boolean;
        nullable?: boolean;
    }>(),
    {
        modelValue: null,
        placeholder: "R$ 0,00",
        disabled: false,
        required: false,
        nullable: false,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: number | null): void;
}>();

const display = ref(centsToMask(props.modelValue));

watch(
    () => props.modelValue,
    (value) => {
        if (value !== parseCurrencyToCents(display.value) || display.value === "") {
            display.value = centsToMask(value);
        }
    },
);

function onInput(event: Event) {
    const target = event.target as HTMLInputElement;
    const masked = maskCurrency(target.value);

    display.value = masked;
    target.value = masked;

    emit(
        "update:modelValue",
        masked === "" && props.nullable ? null : parseCurrencyToCents(masked),
    );
}
</script>

<template>
    <Input
        :id="id"
        :model-value="display"
        inputmode="numeric"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        @input="onInput"
    />
</template>
