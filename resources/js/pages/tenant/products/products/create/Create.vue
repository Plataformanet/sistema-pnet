<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import { parseCurrencyToCents } from "@/lib/masks";
import ProductForm from "../components/ProductForm.vue";

defineOptions({ layout: TenantLayout });

const form = useForm({
    name: "",
    sku: "",
    barcode: "",
    category_id: "",
    cost_value: "",
    sell_value: "",
    manage_stock: true,
    current_stock: "",
    min_stock: "",
    unit_of_measure: "un",
    description: "",
    active: true,
});

function submit() {
    const payload = {
        ...form.data(),
        cost_value: parseCurrencyToCents(form.cost_value as string),
        sell_value: parseCurrencyToCents(form.sell_value as string),
    };
    console.log("Enviando dados do formulário:", payload);
    // form.transform((data) => payload).post(route('tenant.products.products.store'))
}
</script>

<template>
    <Head title="Novo Produto" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Novo Produto
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.products.products.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <ProductForm :form="form" @submit="submit" />
    </div>
</template>
