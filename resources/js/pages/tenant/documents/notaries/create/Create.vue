<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import NotaryForm from "../components/NotaryForm.vue";
import type { Option } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    states: Option[];
}>();

const form = useForm({
    name: "",
    zip_code: "",
    street: "",
    number: "",
    complement: "",
    neighborhood: "",
    city: "",
    state: "",
    reference_point: "",
    business_hours: "",
});

function submit() {
    form.post(route('tenant.documents.notaries.store'));
}
</script>

<template>
    <Head title="Novo Cartório" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Novo Cartório
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.notaries.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <NotaryForm :form="form" :states="props.states" @submit="submit" />
    </div>
</template>
