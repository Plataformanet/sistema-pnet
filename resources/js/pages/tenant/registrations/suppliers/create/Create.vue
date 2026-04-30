<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import SupplierForm from "../components/SupplierForm.vue";

defineOptions({ layout: TenantLayout });

const form = useForm({
    type: "PJ",
    // PF fields
    name: "",
    cpf: "",
    // PJ fields
    corporate_reason: "",
    fantasy_name: "",
    cnpj: "",
    // Supplier specific
    contact_name: "",
    description: "",
    categories: [] as string[],
    // Common fields
    email: "",
    phone: "",
    cellphone: "",
    // Address
    zipcode: "",
    street: "",
    number: "",
    complement: "",
    neighborhood: "",
    city: "",
    state: "",
});

function submit() {
    console.log("Enviando dados do formulário:", form.data());
    // form.post(route('tenant.registrations.suppliers.store'))
}
</script>

<template>
    <Head title="Novo Fornecedor" />

    <div
        class="mb-6 flex items-center justify-between border-b border-border pb-4"
    >
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Novo Fornecedor
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.registrations.suppliers.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <SupplierForm :form="form" @submit="submit" />
    </div>
</template>
