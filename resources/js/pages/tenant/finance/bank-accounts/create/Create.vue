<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import { parseCurrencyToCents } from "@/lib/masks";
import BankAccountForm from "../components/BankAccountForm.vue";

defineOptions({ layout: TenantLayout });

const form = useForm({
    name: "",
    bank: "",
    agency: "",
    account_number: "",
    account_type: "",
    initial_balance: "",
    active: true,
    main_account: false,
});

function submit() {
    const payload = {
        ...form.data(),
        initial_balance: parseCurrencyToCents(form.initial_balance as string),
    };
    form.transform(() => payload).post(route('tenant.finance.bank-accounts.store'));
}
</script>

<template>
    <Head title="Nova Conta Bancária" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Nova Conta Bancária
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.finance.bank-accounts.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <BankAccountForm :form="form" @submit="submit" />
    </div>
</template>
