<script setup lang="ts">
import type { InertiaForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Field, FieldLabel } from "@/components/ui/field";
import FieldError from "@/components/ui/field/FieldError.vue";
import MoneyInput from "@/components/MoneyInput.vue";
import { Percent } from "lucide-vue-next";
import { computed, ref } from "vue";
import DiscountDialog from "./DiscountDialog.vue";
import type { FeeCalculatorFormData, FeeDiscountOption, Option } from "@/types";

/**
 * Formulário de cálculo + observações, usado na tela de cálculo e na de
 * resultado (preenchido com a entrada do cálculo, para recalcular).
 */
const props = defineProps<{
    form: InertiaForm<FeeCalculatorFormData>;
    type: { value: number; label: string; description: string };
    state: string | null;
    municipality: { ibge_code: number | null; name: string | null; itbi_module: string | null };
    financingSystems: Option[];
    discounts: FeeDiscountOption[];
    commonNotes: string;
}>();

const emit = defineEmits<{ (e: "submit"): void }>();

const isPurchase = computed(() => props.type.value === 2);
const asksFirstProperty = computed(() => isPurchase.value && props.municipality.itbi_module === "first_property_rate");
const showDiscounts = ref(false);

const selectedDiscount = computed(() => props.discounts.find((discount) => discount.value === props.form.discount));

/** Campos que não se aplicam ao tipo/município não vão para o backend. */
function submit() {
    props.form.transform((data) => ({
        ...data,
        financing_value: isPurchase.value ? data.financing_value : null,
        financing_system: isPurchase.value ? data.financing_system : null,
        first_property: asksFirstProperty.value ? data.first_property : null,
    }));

    emit("submit");
}
</script>

<template>
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <form @submit.prevent="submit" class="space-y-4">
            <FieldError v-if="form.errors.municipality_name">{{ form.errors.municipality_name }}</FieldError>
            <FieldError v-if="form.errors.state">{{ form.errors.state }}</FieldError>
            <FieldError v-if="form.errors.type">{{ form.errors.type }}</FieldError>

            <Field>
                <FieldLabel for="property_value">Valor do imóvel / Transação <span class="text-destructive">*</span></FieldLabel>
                <MoneyInput id="property_value" v-model="form.property_value" nullable />
                <FieldError v-if="form.errors.property_value">{{ form.errors.property_value }}</FieldError>
            </Field>

            <Field v-if="isPurchase">
                <FieldLabel for="financing_value">Valor do financiamento <span class="text-destructive">*</span></FieldLabel>
                <MoneyInput id="financing_value" v-model="form.financing_value" nullable />
                <FieldError v-if="form.errors.financing_value">{{ form.errors.financing_value }}</FieldError>
            </Field>

            <Field v-if="isPurchase">
                <FieldLabel>Sistema de financiamento <span class="text-destructive">*</span></FieldLabel>
                <div class="flex gap-2">
                    <Button
                        v-for="system in financingSystems"
                        :key="system.value"
                        type="button"
                        :variant="form.financing_system === system.value ? 'default' : 'outline'"
                        @click="form.financing_system = system.value"
                        >{{ system.label }}</Button
                    >
                </div>
                <FieldError v-if="form.errors.financing_system">{{ form.errors.financing_system }}</FieldError>
            </Field>

            <Field v-if="asksFirstProperty">
                <FieldLabel>Primeiro imóvel? <span class="text-destructive">*</span></FieldLabel>
                <div class="flex gap-2">
                    <Button type="button" :variant="form.first_property === true ? 'default' : 'outline'" @click="form.first_property = true">Sim</Button>
                    <Button type="button" :variant="form.first_property === false ? 'default' : 'outline'" @click="form.first_property = false">Não</Button>
                </div>
                <FieldError v-if="form.errors.first_property">{{ form.errors.first_property }}</FieldError>
            </Field>

            <div class="space-y-1">
                <Button type="button" variant="outline" class="w-full" @click="showDiscounts = true">
                    <Percent class="mr-1 h-4 w-4" /> {{ selectedDiscount ? selectedDiscount.label : "Possui desconto?" }}
                </Button>
                <FieldError v-if="form.errors.discount">{{ form.errors.discount }}</FieldError>
            </div>

            <Button type="submit" class="px-12" :loading="form.processing" :disabled="form.processing">Calcular</Button>
        </form>

        <aside class="space-y-4 text-sm leading-relaxed text-muted-foreground">
            <h3 class="text-xl font-semibold text-foreground">Observações importantes:</h3>
            <p>{{ commonNotes }}</p>
            <p>{{ type.description }}</p>
        </aside>

        <DiscountDialog v-model:open="showDiscounts" v-model="form.discount" :discounts="discounts" :state="state" />
    </div>
</template>
