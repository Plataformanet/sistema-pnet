<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/settings/users/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";

defineOptions({ layout: TenantLayout });

export interface User {
    id: string;
    name: string;
    email: string;
    role: string;
    active: boolean;
}

const users: User[] = [
    {
        id: "u1a2b3c4",
        name: "Administrador do Sistema",
        email: "admin@sistema.com",
        role: "Administrador",
        active: true,
    },
    {
        id: "v5x6y7z8",
        name: "João Silva",
        email: "joao.vendas@sistema.com",
        role: "Vendedor",
        active: true,
    },
    {
        id: "w9k0p1o2",
        name: "Maria Oliveira",
        email: "maria.fin@sistema.com",
        role: "Financeiro",
        active: false,
    },
];
</script>

<template>
    <Head title="Lista de usuários" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Lista de usuários
                </h2>
            </div>
            <Button class="cursor-pointer" as-child variant="outline">
                <Link :href="route('tenant.settings.users.create')">
                    <Plus /> Novo usuário
                </Link>
            </Button>
        </div>
        <DataTable :columns="columns" :data="users" />
    </div>
</template>
