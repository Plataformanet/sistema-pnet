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
    type: props.client.contact?.type,
    name_corporatereason: props.client.contact?.name_corporatereason ?? "",
    cpf_cnpj: props.client.contact?.cpf_cnpj ?? "",
    fantasy_name: props.client.contact?.fantasy_name ?? "",
    email: props.client.contact?.email ?? "",
    phone: props.client.contact?.phone ?? "",
    cell_phone: props.client.contact?.cell_phone ?? "",
    zip_code: props.client.contact?.address?.zip_code ?? "",
    street: props.client.contact?.address?.street ?? "",
    number: props.client.contact?.address?.number ?? "",
    complement: props.client.contact?.address?.complement ?? "",
    neighborhood: props.client.contact?.address?.neighborhood ?? "",
    city: props.client.contact?.address?.city ?? "",
    state: props.client.contact?.address?.state ?? "",
});

function submit() {
    form.put(route("tenant.registrations.clients.update", props.client.contact?.id));
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
