<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import ClientForm from "../components/ClientForm.vue";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    client: any;
}>();

const form = useForm({
    type: props.client.type,
    name: props.client.name ?? "",
    cpf: props.client.cpf ?? "",
    corporate_reason: props.client.corporate_reason ?? "",
    fantasy_name: props.client.fantasy_name ?? "",
    cnpj: props.client.cnpj ?? "",
    email: props.client.email ?? "",
    phone: props.client.phone ?? "",
    cellphone: props.client.cellphone ?? "",
    zipcode: props.client.zipcode ?? "",
    street: props.client.street ?? "",
    number: props.client.number ?? "",
    complement: props.client.complement ?? "",
    neighborhood: props.client.neighborhood ?? "",
    city: props.client.city ?? "",
    state: props.client.state ?? "",
});

function submit() {
    form.put(route("tenant.registrations.clients.update", props.client.id));
}
</script>

<template>
    <Head title="Editar Cliente" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
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
