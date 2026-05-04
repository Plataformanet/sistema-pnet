<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import ClientForm from "../components/ClientForm.vue";

defineOptions({ layout: TenantLayout });

const form = useForm({
    type: "PF",
    // PF fields
    name_corporatereason: "",
    cpf_cnpj: "",
    // PJ fields
    fantasy_name: "",
    // Common fields
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
    // Para testar, por enquanto apenas submetemos um console logo.
    // Assim que os endpoints no controller estiverem prontos: form.post(route('alguma.rota.store'))
    form.post(route('tenant.registrations.clients.store'));
}
</script>

<template>
    <Head title="Novo Cliente" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Novo Cliente
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.registrations.clients.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <ClientForm :form="form" @submit="submit" />
    </div>
</template>
