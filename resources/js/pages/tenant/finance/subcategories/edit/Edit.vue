<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import SubcategoryForm from "../components/SubcategoryForm.vue";
import { FinanceCategory, FinanceSubcategory } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    subcategory: FinanceSubcategory;
    categories: FinanceCategory[];
}>();

const form = useForm({
    financial_category_id: props.subcategory.financial_category_id || "",
    name: props.subcategory.name || "",
    active: props.subcategory.active ?? true,
    observations: props.subcategory.observations || "",
});

function submit() {
    form.put(route('tenant.finance.subcategories.update', props.subcategory.id));
}
</script>

<template>
    <Head title="Editar Subcategoria" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Subcategoria Financeira
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.finance.subcategories.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <SubcategoryForm
            :form="form"
            :categories="categories"
            @submit="submit"
            submitText="Atualizar Subcategoria"
        />
    </div>
</template>
