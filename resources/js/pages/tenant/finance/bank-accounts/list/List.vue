<script setup lang="ts">
import { DataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { columns } from "@/pages/tenant/finance/bank-accounts/list/columns";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-vue-next";
import { usePermission } from "@/composables/usePermission";
import { BankAccount } from "@/types";

defineOptions({ layout: TenantLayout });

defineProps<{
    bankAccounts: BankAccount[];
}>();

const { permissions } = usePermission();
</script>

<template>
    <Head title="Contas Bancárias" />
    <div>
        <div
            class="mb-4 flex items-center justify-between border-b border-border pb-4"
        >
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">
                    Contas Bancárias
                </h2>
            </div>

            <Button
                v-if="permissions.includes('finance.accounts.create')"
                class="cursor-pointer"
                as-child
                variant="outline"
            >
                <Link :href="route('tenant.finance.bank-accounts.create')"
                    ><Plus /> Nova conta bancária</Link
                >
            </Button>
        </div>
        <DataTable :columns="columns" :data="bankAccounts" />
    </div>
</template>
