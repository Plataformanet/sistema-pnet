<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import EmployeeForm from "../components/EmployeeForm.vue";

defineOptions({ layout: TenantLayout });

const form = useForm({
    // Employee specifics
    name_corporatereason: "",
    cpf_cnpj: "",
    rg: "",
    birth_date: "",
    position: "",
    salary: "",
    hire_date: "",

    // Contact
    email: "",
    phone: "",
    cell_phone: "",

    // Address
    zip_code: "",
    street: "",
    number: "",
    complement: "",
    neighborhood: "",
    city: "",
    state: "",
});

function submit() {
    // console.log("Enviando dados do formulário de funcionário:", form.data());
    form.post(route('tenant.registrations.employees.store'))
}
</script>

<template>
    <Head title="Novo Funcionário" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Novo Funcionário
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.registrations.employees.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <EmployeeForm :form="form" @submit="submit" />
    </div>
</template>
