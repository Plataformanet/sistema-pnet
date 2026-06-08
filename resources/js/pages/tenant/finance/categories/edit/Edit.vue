<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import CategoryForm from "../components/CategoryForm.vue";
import { FinanceCategory } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    category: FinanceCategory;
}>();

const form = useForm({
    name: props.category.name || "",
    type: props.category.type || "despesa",
    active: props.category.active ?? true,
    observations: props.category.observations || "",
});

function submit() {
    form.put(route('tenant.finance.categories.update', props.category.id));
}
</script>

<template>
    <Head title="Editar Categoria Financeira" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Categoria Financeira
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.finance.categories.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <CategoryForm :form="form" @submit="submit" submitText="Atualizar Categoria" />
    </div>
</template>
