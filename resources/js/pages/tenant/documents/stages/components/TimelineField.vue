<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import FieldError from "@/components/ui/field/FieldError.vue";

defineProps<{
    name: string;
    error?: string;
}>();
</script>

<template>
    <Field class="max-w-md">
        <FieldLabel for="timeline">Timeline</FieldLabel>
        <Input id="timeline" :model-value="name" disabled />
        <FieldDescription>A timeline é definida na criação e não pode ser alterada.</FieldDescription>
        <FieldError v-if="error">{{ error }}</FieldError>
    </Field>
</template>
