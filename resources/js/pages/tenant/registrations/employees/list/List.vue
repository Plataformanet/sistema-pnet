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
    cpf_cnpj?: string;
    position?: string;
    email: string;
}

defineProps<{
    employees: Employee[];
}>();

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
