<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import { maskCurrency, parseCurrencyToCents } from "@/lib/masks";
import ProductForm from "../components/ProductForm.vue";

defineOptions({ layout: TenantLayout });

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.product.name || "",
    sku: props.product.sku || "",
    barcode: props.product.barcode || "",
    category_id: props.product.category_id || "",
    cost_value: props.product.cost_value !== undefined ? maskCurrency(String(props.product.cost_value)) : "",
    sell_value: props.product.sell_value !== undefined ? maskCurrency(String(props.product.sell_value)) : "",
    manage_stock: props.product.manage_stock ?? true,
    current_stock: props.product.current_stock || "",
    min_stock: props.product.min_stock || "",
    unit_of_measure: props.product.unit_of_measure || "un",
    description: props.product.description || "",
    active: props.product.active ?? true,
});

function submit() {
    const payload = {
        ...form.data(),
        cost_value: parseCurrencyToCents(form.cost_value as string),
        sell_value: parseCurrencyToCents(form.sell_value as string),
    };
    console.log("Atualizando dados do formulário:", payload);
    // form.transform((data) => payload).put(route('tenant.products.products.update', props.product.id))
}
</script>

<template>
    <Head title="Editar Produto" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Produto
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.products.products.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <ProductForm :form="form" @submit="submit" submitText="Atualizar Produto" />
    </div>
</template>
