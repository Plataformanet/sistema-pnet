<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/registrations/clients/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";

defineOptions({ layout: TenantLayout });

export interface Client {
    id: string;
    name: string;
    email: string;
    cpf_cnpj?: string;
    // cnpj?: string;
}

const clients: Client[] = [
    {
        id: "728ed52f",
        name: "Alan Varela Ferreiras",
        email: "m@example.com",
        cpf_cnpj: "449.750.198-17",
    },
    {
        id: "349bc91a",
        name: "Maria Silva Santos",
        email: "maria.silva@example.com",
        cpf_cnpj: "123.456.789-00",
    },
    {
        id: "8f1a2b3c",
        name: "Empresa XPTO",
        email: "contato@xpto.com.br",
        cpf_cnpj: "12.345.678/0001-90",
    },
    {
        id: "9d8e7f6a",
        name: "Ana Costa",
        email: "ana.costa@example.com",
        cpf_cnpj: "987.654.321-11",
    },
    {
        id: "5c4b3a21",
        name: "Tech Solutions LTDA",
        email: "contato@techsolutions.com.br",
        cpf_cnpj: "98.765.432/0001-21",
    },
    {
        id: "728ed52f",
        name: "Alan Varela Ferreira",
        email: "m@example.com",
        cpf_cnpj: "449.750.198-17",
    },
    {
        id: "349bc91a",
        name: "Maria Silva Santos",
        email: "maria.silva@example.com",
        cpf_cnpj: "123.456.789-00",
    },
    {
        id: "8f1a2b3c",
        name: "Empresa XPTO",
        email: "contato@xpto.com.br",
        cpf_cnpj: "12.345.678/0001-90",
    },
    {
        id: "9d8e7f6a",
        name: "Ana Costa",
        email: "ana.costa@example.com",
        cpf_cnpj: "987.654.321-11",
    },
    {
        id: "5c4b3a21",
        name: "Tech Solutions LTDA",
        email: "contato@techsolutions.com.br",
        cpf_cnpj: "98.765.432/0001-21",
    },
    {
        id: "728ed52f",
        name: "Alan Varela Ferreira",
        email: "m@example.com",
        cpf_cnpj: "449.750.198-17",
    },
    {
        id: "349bc91a",
        name: "Maria Silva Santos",
        email: "maria.silva@example.com",
        cpf_cnpj: "123.456.789-00",
    },
    {
        id: "8f1a2b3c",
        name: "Empresa XPTO",
        email: "contato@xpto.com.br",
        cpf_cnpj: "12.345.678/0001-90",
    },
    {
        id: "9d8e7f6a",
        name: "Ana Costa",
        email: "ana.costa@example.com",
        cpf_cnpj: "987.654.321-11",
    },
    {
        id: "5c4b3a21",
        name: "Tech Solutions LTDA",
        email: "contato@techsolutions.com.br",
        cpf_cnpj: "98.765.432/0001-21",
    },
    {
        id: "728ed52f",
        name: "Alan Varela Ferreira",
        email: "m@example.com",
        cpf_cnpj: "449.750.198-17",
    },
    {
        id: "349bc91a",
        name: "Maria Silva Santos",
        email: "maria.silva@example.com",
        cpf_cnpj: "123.456.789-00",
    },
    {
        id: "8f1a2b3c",
        name: "Empresa XPTO",
        email: "contato@xpto.com.br",
        cpf_cnpj: "12.345.678/0001-90",
    },
    {
        id: "9d8e7f6a",
        name: "Ana Costa",
        email: "ana.costa@example.com",
        cpf_cnpj: "987.654.321-11",
    },
    {
        id: "5c4b3a21",
        name: "Tech Solutions LTDA",
        email: "contato@techsolutions.com.br",
        cpf_cnpj: "98.765.432/0001-21",
    },
];
</script>

<template>
    <Head title="Lista de clientes" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Lista de clientes
                </h2>
            </div>

            <Button class="cursor-pointer" as-child variant="outline">
                <Link :href="route('tenant.registrations.clients.create')"
                    ><Plus /> Novo cliente</Link
                >
            </Button>
        </div>
        <DataTable :columns="columns" :data="clients" />
    </div>
</template>
