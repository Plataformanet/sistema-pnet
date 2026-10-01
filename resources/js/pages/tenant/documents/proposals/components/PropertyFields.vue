<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import FieldError from "@/components/ui/field/FieldError.vue";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { computed } from "vue";
import type { Development, PropertyType } from "@/types";

/**
 * Campos do imóvel: cada flag do tipo de imóvel mostra/oculta o seu campo e,
 * ao trocar de tipo, os campos que ficaram ocultos são limpos.
 */
const props = defineProps<{
    property: Record<string, any>;
    propertyTypes: PropertyType[];
    developments: Pick<Development, "id" | "name">[];
    errors: Record<string, string>;
    disabled?: boolean;
}>();

const selectedType = computed(() =>
    props.propertyTypes.find((type) => type.id === Number(props.property.property_type_id)),
);

function onTypeChange(value: unknown) {
    props.property.property_type_id = value ? Number(value) : null;

    const type = selectedType.value;

    if (!type?.requires_development) props.property.development_id = null;
    if (!type?.shows_number) props.property.number = "";
    if (!type?.shows_complement) props.property.complement = "";
    if (!type?.shows_unit) props.property.unit = "";
    if (!type?.shows_block) props.property.block = "";
}
</script>

<template>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <Field>
            <FieldLabel for="property_type_id">Tipo de imóvel</FieldLabel>
            <Select
                :model-value="property.property_type_id ? String(property.property_type_id) : 'none'"
                @update:model-value="onTypeChange($event === 'none' ? null : $event)"
                :disabled="disabled"
            >
                <SelectTrigger id="property_type_id">
                    <SelectValue placeholder="Selecione..." />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem value="none">Não informar imóvel</SelectItem>
                        <SelectItem
                            v-for="type in propertyTypes"
                            :key="type.id"
                            :value="String(type.id)"
                            >{{ type.name }}</SelectItem
                        >
                    </SelectGroup>
                </SelectContent>
            </Select>
            <FieldError v-if="errors['property.property_type_id']">{{ errors["property.property_type_id"] }}</FieldError>
        </Field>

        <Field v-if="selectedType?.requires_development">
            <FieldLabel for="development_id">Empreendimento *</FieldLabel>
            <Select
                :model-value="property.development_id ? String(property.development_id) : ''"
                @update:model-value="property.development_id = Number($event)"
                :disabled="disabled"
            >
                <SelectTrigger id="development_id">
                    <SelectValue placeholder="Selecione o empreendimento..." />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem
                            v-for="development in developments"
                            :key="development.id"
                            :value="String(development.id)"
                            >{{ development.name }}</SelectItem
                        >
                    </SelectGroup>
                </SelectContent>
            </Select>
            <FieldError v-if="errors['property.development_id']">{{ errors["property.development_id"] }}</FieldError>
        </Field>

        <template v-if="selectedType">
            <Field class="md:col-span-2">
                <FieldLabel for="property_address">Endereço</FieldLabel>
                <Input id="property_address" v-model="property.address" :disabled="disabled" />
                <FieldError v-if="errors['property.address']">{{ errors["property.address"] }}</FieldError>
            </Field>
            <Field v-if="selectedType.shows_number">
                <FieldLabel for="property_number">Número</FieldLabel>
                <Input id="property_number" v-model="property.number" :disabled="disabled" />
            </Field>
            <Field v-if="selectedType.shows_complement">
                <FieldLabel for="property_complement">Complemento</FieldLabel>
                <Input id="property_complement" v-model="property.complement" :disabled="disabled" />
            </Field>
            <Field v-if="selectedType.shows_block">
                <FieldLabel for="property_block">Bloco</FieldLabel>
                <Input id="property_block" v-model="property.block" :disabled="disabled" />
            </Field>
            <Field v-if="selectedType.shows_unit">
                <FieldLabel for="property_unit">Unidade</FieldLabel>
                <Input id="property_unit" v-model="property.unit" :disabled="disabled" />
            </Field>
        </template>
    </div>
</template>
