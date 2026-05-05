<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import ClientForm from "../components/ClientForm.vue";

import { Client } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    client: Client;
}>();

const form = useForm({
    type: props.client.type,
    name_corporatereason: props.client.name_corporatereason ?? "",
    cpf_cnpj: props.client.cpf_cnpj ?? "",
    fantasy_name: props.client.fantasy_name ?? "",
    email: props.client.email ?? "",
    phone: props.client.phone ?? "",
    cell_phone: props.client.cell_phone ?? "",
    zip_code: props.client.address?.zip_code ?? "",
    street: props.client.address?.street ?? "",
    number: props.client.address?.number ?? "",
    complement: props.client.address?.complement ?? "",
    neighborhood: props.client.address?.neighborhood ?? "",
    city: props.client.address?.city ?? "",
    state: props.client.address?.state ?? "",
});

function submit() {
    form.put(route("tenant.registrations.clients.update", props.client.id));
}
</script>

<template>
    <Head title="Editar Cliente" />

    <div
        class="mb-6 flex items-center justify-between border-b border-border pb-4"
    >
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Cliente
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.registrations.clients.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <ClientForm
            :form="form"
            submitText="Atualizar Cliente"
            @submit="submit"
        />
    </div>
</template>
