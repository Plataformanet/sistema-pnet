<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/registrations/suppliers/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";

defineOptions({ layout: TenantLayout });

export interface Supplier {
    id: string;
    name: string;
    email: string;
    category?: string;
    cpf?: string;
    cnpj?: string;
}

const suppliers: Supplier[] = [
    {
        id: "728ed52f",
        name: "Fornecedor X LTDA",
        email: "vendas@fornecedorx.com.br",
        cnpj: "12.345.678/0001-90",
        category: "Equipamentos"
    },
    {
        id: "349bc91a",
        name: "Limpeza & Cia",
        email: "contato@limpezaecia.com.br",
        cnpj: "98.765.432/0001-21",
        category: "Limpeza"
    },
    {
        id: "8f1a2b3c",
        name: "João Autônomo",
        email: "joao.servicos@example.com",
        cpf: "123.456.789-00",
        category: "Manutenção"
    },
];
</script>

<template>
    <Head title="Lista de fornecedores" />
    <div>
        <div class="mb-4 flex items-center justify-between border-b border-border pb-4">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Lista de fornecedores
                </h2>
            </div>
            <Button class="cursor-pointer" as-child variant="outline">
                <Link :href="route('tenant.registrations.suppliers.create')">
                    <Plus /> Novo fornecedor
                </Link>
            </Button>
        </div>
        <DataTable :columns="columns" :data="suppliers" />
    </div>
</template>
