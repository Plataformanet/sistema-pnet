<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import FieldError from "@/components/ui/field/FieldError.vue";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { useForm } from "@inertiajs/vue3";
import type { StatusOption } from "@/types";

/**
 * Status manual da proposta com os campos que cada status exige.
 * "Finalizada" só aparece quando a proposta já foi finalizada pela timeline.
 */
defineProps<{
    form: ReturnType<typeof useForm>;
    statuses: StatusOption[];
    finishedAt?: string | null;
    disabled?: boolean;
}>();
</script>

<template>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <Field>
            <FieldLabel for="status">Status *</FieldLabel>
            <Select
                :model-value="String(form.status)"
                @update:model-value="form.status = String($event)"
                :disabled="disabled || form.status === 'finished'"
            >
                <SelectTrigger id="status">
                    <SelectValue placeholder="Selecione o status..." />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem v-if="form.status === 'finished'" value="finished">Finalizada</SelectItem>
                        <SelectItem v-if="form.status === 'new'" value="new" disabled>Nova Proposta</SelectItem>
                        <SelectItem v-for="status in statuses" :key="status.value" :value="status.value">{{
                            status.label
                        }}</SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
            <FieldDescription v-if="form.status === 'finished' && finishedAt">
                Finalizada em {{ new Date(finishedAt).toLocaleDateString("pt-BR") }}.
            </FieldDescription>
            <FieldError v-if="form.errors.status">{{ form.errors.status }}</FieldError>
        </Field>

        <template v-if="form.status === 'awaiting_property'">
            <Field>
                <FieldLabel>Previsão de entrega (MM/AAAA) *</FieldLabel>
                <div class="flex gap-2">
                    <Input v-model.number="form.expected_delivery_month" type="number" min="1" max="12" placeholder="MM" class="w-24" :disabled="disabled" />
                    <Input v-model.number="form.expected_delivery_year" type="number" min="2000" max="2100" placeholder="AAAA" class="w-32" :disabled="disabled" />
                </div>
                <FieldError v-if="form.errors.expected_delivery_month">{{ form.errors.expected_delivery_month }}</FieldError>
                <FieldError v-if="form.errors.expected_delivery_year">{{ form.errors.expected_delivery_year }}</FieldError>
            </Field>
        </template>

        <Field v-if="form.status === 'canceled'" class="md:col-span-2">
            <FieldLabel for="cancellation_reason">Motivo do cancelamento *</FieldLabel>
            <Textarea id="cancellation_reason" v-model="form.cancellation_reason" :disabled="disabled" />
            <FieldError v-if="form.errors.cancellation_reason">{{ form.errors.cancellation_reason }}</FieldError>
        </Field>

        <Field v-if="form.status === 'restricted'" class="md:col-span-2">
            <FieldLabel for="restriction_reason">Motivo da restrição *</FieldLabel>
            <Textarea id="restriction_reason" v-model="form.restriction_reason" :disabled="disabled" />
            <FieldError v-if="form.errors.restriction_reason">{{ form.errors.restriction_reason }}</FieldError>
        </Field>
    </div>
</template>
