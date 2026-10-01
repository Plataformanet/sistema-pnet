<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import StateMunicipalityPicker from "@/pages/tenant/documents/fee-calculator/components/StateMunicipalityPicker.vue";
import { useForm } from "@inertiajs/vue3";
import type { ItbiModuleOption, SupportedStateOption } from "@/types";

const props = withDefaults(
    defineProps<{
        form: ReturnType<typeof useForm>;
        states: SupportedStateOption[];
        modules: ItbiModuleOption[];
        submitText?: string;
        isEdit?: boolean;
    }>(),
    { submitText: "Salvar Município", isEdit: false },
);

const emit = defineEmits(["submit"]);

function onPick(value: { state: string; ibgeCode: number; name: string } | null) {
    props.form.state = value?.state ?? null;
    props.form.ibge_code = value?.ibgeCode ?? null;
    props.form.name = value?.name ?? "";
}
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-8 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8">
        <div>
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">Município</h3>
            <StateMunicipalityPicker :states="states" :state="form.state" :ibge-code="form.ibge_code" @change="onPick" />
            <FieldError v-if="form.errors.ibge_code">{{ form.errors.ibge_code }}</FieldError>
            <FieldError v-if="form.errors.name">{{ form.errors.name }}</FieldError>
        </div>

        <Field>
            <FieldLabel for="module">Módulo de cálculo *</FieldLabel>
            <Select :model-value="form.module ?? ''" @update:model-value="form.module = String($event)">
                <SelectTrigger id="module">
                    <SelectValue placeholder="Selecione o módulo..." />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem v-for="module in modules" :key="module.value" :value="module.value">{{ module.label }}</SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
            <FieldDescription v-if="isEdit">Trocar o módulo apaga as alíquotas/faixas do módulo anterior.</FieldDescription>
            <FieldError v-if="form.errors.module">{{ form.errors.module }}</FieldError>
        </Field>

        <div class="flex justify-end border-t border-border pt-6">
            <Button type="submit" class="text-md w-full px-10 font-bold md:w-auto" :loading="form.processing" :disabled="form.processing">
                {{ submitText }}
            </Button>
        </div>
    </form>
</template>
