<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/settings/roles/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";

defineOptions({ layout: TenantLayout });

export interface Role {
    id: number;
    name: string;
    users_count: number;
}

const roles: Role[] = [
    {
        id: 1,
        name: "Administrador",
        users_count: 3
    },
    {
        id: 2,
        name: "Vendedor",
        users_count: 15
    },
    {
        id: 3,
        name: "Financeiro",
        users_count: 2
    },
];
</script>

<template>
    <Head title="Lista de Cargos" />
    <div>
        <div class="mb-4 flex items-center justify-between border-b border-border pb-4">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Cargos e Perfis
                </h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Gerencie os cargos e as permissões de acesso dos usuários.
                </p>
            </div>
            <Button class="cursor-pointer" as-child variant="outline">
                <Link :href="route('tenant.settings.roles.create')">
                    <Plus /> Novo cargo
                </Link>
            </Button>
        </div>
        <DataTable :columns="columns" :data="roles" />
    </div>
</template>
