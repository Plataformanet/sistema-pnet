<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/services/services/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import { Service } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    services: Service[];
}>();

const { permissions } = usePermission();
</script>

<template>
    <Head title="Lista de serviços" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Lista de serviços
                </h2>
            </div>

            <Button
                v-if="permissions.includes('services.services.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.services.services.create')">
                    <Plus /> Novo serviço
                </Link>
            </Button>
        </div>
        <DataTable :columns="columns" :data="services" />
    </div>
</template>
