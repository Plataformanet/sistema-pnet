<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/registrations/clients/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { Client } from "@/types";
import { usePermission } from "@/composables/usePermission";
import { computed, ref } from "vue";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    clients: Client[];
}>();

const { permissions } = usePermission();

type StatusFilter = "all" | "active" | "inactive";

const statusFilter = ref<StatusFilter>("all");

const statusOptions: { label: string; value: StatusFilter }[] = [
    { label: "Todos", value: "all" },
    { label: "Ativos", value: "active" },
    { label: "Inativos", value: "inactive" },
];

const filteredClients = computed(() =>
    props.clients.filter((client) => {
        const isActive = client.active ?? true;
        if (statusFilter.value === "active") return isActive;
        if (statusFilter.value === "inactive") return !isActive;
        return true;
    }),
);

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

            <Button v-if="permissions.includes('registrations.clients.create')" class="cursor-pointer" as-child variant="outline">
                <Link :href="route('tenant.registrations.clients.create')"><Plus /> Novo cliente</Link>
            </Button>
        </div>
        <div class="mb-4 flex gap-2">
            <Button
                v-for="option in statusOptions"
                :key="option.value"
                type="button"
                size="sm"
                :variant="statusFilter === option.value ? 'default' : 'outline'"
                class="cursor-pointer"
                @click="statusFilter = option.value"
            >
                {{ option.label }}
            </Button>
        </div>
        <DataTable :columns="columns" :data="filteredClients" />
    </div>
</template>
