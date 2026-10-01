<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import ContractTypeForm from "../components/ContractTypeForm.vue";

defineOptions({ layout: TenantLayout });

const form = useForm({
    name: "",
    requires_financing: true,
});

function submit() {
    form.post(route('tenant.documents.contract-types.store'));
}
</script>

<template>
    <Head title="Novo Tipo de Contrato" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Novo Tipo de Contrato
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.contract-types.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <ContractTypeForm :form="form" @submit="submit" />
    </div>
</template>
