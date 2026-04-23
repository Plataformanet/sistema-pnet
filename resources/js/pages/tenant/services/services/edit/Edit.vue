<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import { maskCurrency, parseCurrencyToCents } from "@/lib/masks";
import ServiceForm from "../components/ServiceForm.vue";

defineOptions({ layout: TenantLayout });

const props = defineProps({
    service: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.service.name || "",
    sku: props.service.sku || "",
    cost_value: props.service.cost_value !== undefined ? maskCurrency(String(props.service.cost_value)) : "",
    sell_value: props.service.sell_value !== undefined ? maskCurrency(String(props.service.sell_value)) : "",
    fees: props.service.fees !== undefined ? maskCurrency(String(props.service.fees)) : "",
    category_id: props.service.category_id || "",
    description: props.service.description || "",
    duration: props.service.duration || "",
    active: props.service.active ?? true,
});

function submit() {
    const payload = {
        ...form.data(),
        cost_value: parseCurrencyToCents(form.cost_value as string),
        sell_value: parseCurrencyToCents(form.sell_value as string),
        fees: parseCurrencyToCents(form.fees as string),
    };
    console.log("Atualizando dados do formulário:", payload);
    // form.transform((data) => payload).put(route('tenant.services.services.update', props.service.id))
}
</script>

<template>
    <Head title="Editar Serviço" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Serviço
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.services.services.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <ServiceForm :form="form" @submit="submit" submitText="Atualizar Serviço" />
    </div>
</template>
