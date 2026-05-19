<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/registrations/clients/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { Client } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    clients: Client[];
}>();

console.log(props.clients);
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
