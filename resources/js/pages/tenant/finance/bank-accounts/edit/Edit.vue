<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import { maskCurrency, parseCurrencyToCents } from "@/lib/masks";
import BankAccountForm from "../components/BankAccountForm.vue";
import { BankAccount } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    bankAccount: BankAccount;
}>();

const form = useForm({
    name: props.bankAccount.name || "",
    bank: props.bankAccount.bank || "",
    agency: props.bankAccount.agency || "",
    account_number: props.bankAccount.account_number || "",
    account_type: props.bankAccount.account_type || "",
    initial_balance: props.bankAccount.initial_balance !== undefined && props.bankAccount.initial_balance !== null
        ? maskCurrency(String(props.bankAccount.initial_balance))
        : "",
    active: props.bankAccount.active ?? true,
    main_account: props.bankAccount.main_account ?? false,
});

function submit() {
    const payload = {
        ...form.data(),
        initial_balance: parseCurrencyToCents(form.initial_balance as string),
    };
    form.transform(() => payload).put(route('tenant.finance.bank-accounts.update', props.bankAccount.id));
}
</script>

<template>
    <Head title="Editar Conta Bancária" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Conta Bancária
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.finance.bank-accounts.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <BankAccountForm :form="form" @submit="submit" submitText="Atualizar Conta Bancária" />
    </div>
</template>
