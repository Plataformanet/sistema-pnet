<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import SubcategoryForm from "../components/SubcategoryForm.vue";
import { FinanceCategory } from "@/types";

defineOptions({ layout: TenantLayout });

defineProps<{
    categories: FinanceCategory[];
}>();

const form = useForm({
    financial_category_id: "",
    name: "",
    active: true,
    observations: "",
});

function submit() {
    form.post(route('tenant.finance.subcategories.store'));
}
</script>

<template>
    <Head title="Nova Subcategoria" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Nova Subcategoria Financeira
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.finance.subcategories.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <SubcategoryForm :form="form" :categories="categories" @submit="submit" />
    </div>
</template>
