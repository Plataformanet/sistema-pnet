<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/settings/roles/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import type { Role } from "@/types";

defineOptions({ layout: TenantLayout });

defineProps<{
    roles: Role[];
}>();

const { permissions } = usePermission();
</script>

<template>
    <Head title="Lista de Cargos" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Cargos e Perfis
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Gerencie os cargos e as permissões de acesso dos usuários.
                </p>
            </div>
            <Button
                v-if="permissions.includes('settings.roles.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.settings.roles.create')">
                    <Plus /> Novo cargo
                </Link>
            </Button>
        </div>
        <DataTable :columns="columns" :data="roles" />
    </div>
</template>
