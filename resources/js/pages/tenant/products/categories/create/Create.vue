<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import CategoryForm from "../components/CategoryForm.vue";

defineOptions({ layout: TenantLayout });

const form = useForm({
    name: "",
    active: true,
});

function submit() {
    console.log("Enviando dados do formulário:", form.data());
    // form.post(route('tenant.products.categories.store'))
}
</script>

<template>
    <Head title="Nova Categoria de Produto" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Nova Categoria
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.products.categories.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <CategoryForm :form="form" @submit="submit" />
    </div>
</template>
