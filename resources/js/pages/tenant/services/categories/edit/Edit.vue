<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import CategoryForm from "../components/CategoryForm.vue";

defineOptions({ layout: TenantLayout });

const props = defineProps({
    category: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.category.name || "",
    active: props.category.active ?? true,
});

function submit() {
    console.log("Atualizando dados do formulário:", form.data());
    // form.put(route('tenant.services.categories.update', props.category.id))
}
</script>

<template>
    <Head title="Editar Categoria" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Categoria
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.services.categories.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <CategoryForm :form="form" @submit="submit" submitText="Atualizar Categoria" />
    </div>
</template>
