<script setup lang="ts">
import { onMounted, ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import { parseCurrencyToCents } from "@/lib/masks";
import AccountPayableForm from "../components/AccountPayableForm.vue";
import {
    FinanceCategory,
    FinanceSubcategory,
    Cost,
    Contact,
    BankAccount
} from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    financialCategories: FinanceCategory[];
    financialSubcategories: Array<string | FinanceSubcategory>;
    costs: Cost[];
    contacts: Contact[];
    paymentConditions: Record<string, string>;
    bankAccounts: BankAccount[];
}>();

const form = useForm({
    financial_category_id: "",
    financial_subcategory_id: "",
    cost_id: "",
    bank_account_id: "",
    financial_contact_id: "",
    description: "",
    total: "",
    payment_method: "" as string | number,
    payment_condition: "",
    total_installments: 1,
    bank_account_out: "" as string | number,
    observations: "",
    receipt: "",
    value: "",
    due_date: new Date().toLocaleDateString("en-CA"),
    status: "open",
    installments: [] as any[],
});

const backUrl = ref(route("tenant.finance.accounts-payable.list"));
const submitUrl = ref(route("tenant.finance.accounts-payable.store"));

onMounted(() => {
    if (window.location.search) {
        backUrl.value = route("tenant.finance.accounts-payable.list") + window.location.search;
        submitUrl.value = route("tenant.finance.accounts-payable.store") + window.location.search;
    }
});

function submit() {
    // Transform values back to backend format (ints / cents)
    const payload = {
        ...form.data(),
        financial_category_id: form.financial_category_id ? Number(form.financial_category_id) : null,
        financial_subcategory_id: form.financial_subcategory_id ? Number(form.financial_subcategory_id) : null,
        cost_id: form.cost_id ? Number(form.cost_id) : null,
        bank_account_id: form.bank_account_id ? Number(form.bank_account_id) : null,
        financial_contact_id: form.financial_contact_id ? Number(form.financial_contact_id) : null,
        total: parseCurrencyToCents(form.total as string),
        value: parseCurrencyToCents(form.value as string),
        payment_method: form.payment_method || null,
        bank_account_out: form.bank_account_out ? Number(form.bank_account_out) : null,
    };
    
    form.transform(() => payload).post(submitUrl.value);
}
</script>

<template>
    <Head title="Novo Lançamento - Contas a Pagar" />

    <div class="mb-6 flex flex-col gap-4 border-b border-border pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Novo Lançamento
            </h2>
            <p class="text-sm text-muted-foreground">Registre um novo contas a pagar no financeiro.</p>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="backUrl">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <AccountPayableForm
            :form="form"
            :financial-categories="props.financialCategories"
            :financial-subcategories="props.financialSubcategories"
            :costs="props.costs"
            :contacts="props.contacts"
            :payment-conditions="props.paymentConditions"
            :bank-accounts="props.bankAccounts"
            @submit="submit"
            submitText="Salvar Lançamento"
        />
    </div>
</template>
