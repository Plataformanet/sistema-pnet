<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/registrations/employees/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";

defineOptions({ layout: TenantLayout });

export interface Employee {
    id: string;
    name: string;
    cpf?: string;
    position?: string;
    email: string;
}

const employees: Employee[] = [
    {
        id: "e1f2g3h4",
        name: "Carlos Eduardo Costa",
        email: "carlos.costa@empresa.com",
        cpf: "111.222.333-44",
        position: "Vendedor"
    },
    {
        id: "j5k6l7m8",
        name: "Juliana Mendes",
        email: "juliana.mendes@empresa.com",
        cpf: "555.666.777-88",
        position: "Gerente de Vendas"
    },
    {
        id: "n9o0p1q2",
        name: "Marcos Antônio",
        email: "marcos.antonio@empresa.com",
        cpf: "999.000.111-22",
        position: "Assistente Administrativo"
    },
];
</script>

<template>
    <Head title="Lista de funcionários" />
    <div>
        <div class="mb-4 flex items-center justify-between border-b border-border pb-4">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Lista de funcionários
                </h2>
            </div>
            <Button class="cursor-pointer" as-child variant="outline">
                <Link :href="route('tenant.registrations.employees.create')">
                    <Plus /> Novo funcionário
                </Link>
            </Button>
        </div>
        <DataTable :columns="columns" :data="employees" />
    </div>
</template>
