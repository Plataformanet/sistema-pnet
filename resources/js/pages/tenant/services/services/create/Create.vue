<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import { parseCurrencyToCents } from "@/lib/masks";
import ServiceForm from "../components/ServiceForm.vue";

defineOptions({ layout: TenantLayout });

const form = useForm({
    name: "",
    sku: "",
    cost_value: "",
    sell_value: "",
    fees: "",
    category_id: "",
    description: "",
    duration: "",
    active: true,
});

function submit() {
    const payload = {
        ...form.data(),
        cost_value: parseCurrencyToCents(form.cost_value as string),
        sell_value: parseCurrencyToCents(form.sell_value as string),
        fees: parseCurrencyToCents(form.fees as string),
    };
    console.log("Enviando dados do formulário:", payload);
    // form.transform((data) => payload).post(route('tenant.services.services.store'))
}
</script>

<template>
    <Head title="Novo Serviço" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Novo Serviço
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.services.services.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <ServiceForm :form="form" @submit="submit" />
    </div>
</template>
