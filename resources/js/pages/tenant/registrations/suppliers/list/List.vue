<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/registrations/suppliers/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { Supplier } from "@/types";
import { usePermission } from "@/composables/usePermission";
import { computed, ref } from "vue";

defineOptions({ layout: TenantLayout });


const props = defineProps<{
    suppliers: Supplier[];
}>();

const { permissions } = usePermission();

type StatusFilter = "all" | "active" | "inactive";

const statusFilter = ref<StatusFilter>("all");

const statusOptions: { label: string; value: StatusFilter }[] = [
    { label: "Todos", value: "all" },
    { label: "Ativos", value: "active" },
    { label: "Inativos", value: "inactive" },
];

const filteredSuppliers = computed(() =>
    props.suppliers.filter((supplier) => {
        const isActive = supplier.active ?? true;
        if (statusFilter.value === "active") return isActive;
        if (statusFilter.value === "inactive") return !isActive;
        return true;
    }),
);

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
            <Button v-if="permissions.includes('registrations.suppliers.create')" class="cursor-pointer" as-child variant="outline">
                <Link :href="route('tenant.registrations.suppliers.create')">
                    <Plus /> Novo fornecedor
                </Link>
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
        <DataTable :columns="columns" :data="filteredSuppliers" />
    </div>
</template>
