<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import PropertyTypeForm from "../components/PropertyTypeForm.vue";
import type { PropertyType } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    propertyType: PropertyType;
}>();

const form = useForm({
    name: props.propertyType.name || "",
    shows_number: props.propertyType.shows_number ?? false,
    shows_complement: props.propertyType.shows_complement ?? false,
    requires_development: props.propertyType.requires_development ?? false,
    shows_unit: props.propertyType.shows_unit ?? false,
    shows_block: props.propertyType.shows_block ?? false,
});

function submit() {
    form.put(route('tenant.documents.property-types.update', props.propertyType.id));
}
</script>

<template>
    <Head title="Editar Tipo de Imóvel" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Tipo de Imóvel
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.property-types.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <PropertyTypeForm :form="form" @submit="submit" submitText="Atualizar Tipo de Imóvel" />
    </div>
</template>
