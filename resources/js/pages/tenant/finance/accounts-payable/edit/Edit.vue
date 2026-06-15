<script setup lang="ts">
import { onMounted, ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import { maskCurrency, parseCurrencyToCents } from "@/lib/masks";
import AccountPayableForm from "../components/AccountPayableForm.vue";
import {
    AccountPayable,
    FinanceCategory,
    Cost,
    Contact,
    BankAccount,
} from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    accountPayable: AccountPayable;
    financialCategories: FinanceCategory[];
    financialSubcategories: Array<{ id: string | number; name: string }>;
    costs: Cost[];
    contacts: Contact[];
    paymentConditions: Record<string, string>;
    bankAccounts: BankAccount[];
}>();

// Helper to normalize backend payment methods (like 'money', 'pix' enum cases) to front-end keys
function normalizePaymentMethod(
    val: string | number | undefined | null,
): string {
    if (val === undefined || val === null) return "";
    const s = String(val).toLowerCase();
    if (s.includes("money") || s.includes("dinheiro")) return "money";
    if (s.includes("pix")) return "pix";
    if (s.includes("ticket") || s.includes("boleto")) return "ticket";
    if (s.includes("credit") || s.includes("cartao") || s.includes("cartão"))
        return "credit_card";
    return s;
}

const form = useForm({
    financial_category_id: props.accountPayable.financial_category_id
        ? String(props.accountPayable.financial_category_id)
        : "",
    financial_subcategory_id: props.accountPayable.financial_subcategory_id
        ? String(props.accountPayable.financial_subcategory_id)
        : "",
    cost_id: props.accountPayable.cost_id
        ? String(props.accountPayable.cost_id)
        : "",
    bank_account_id: props.accountPayable.bank_account_id
        ? String(props.accountPayable.bank_account_id)
        : "",
    financial_contact_id: props.accountPayable.financial_contact?.contact_id
        ? String(props.accountPayable.financial_contact.contact_id)
        : "",
    description: props.accountPayable.description || "",
    total:
        props.accountPayable.total !== undefined &&
        props.accountPayable.total !== null
            ? maskCurrency(String(props.accountPayable.total))
            : "",
    payment_method: normalizePaymentMethod(
        props.accountPayable.payment_method,
    ) as string | number,
    payment_condition: props.accountPayable.payment_condition || "",
    total_installments: props.accountPayable.total_installments || 1,
    bank_account_out: props.accountPayable.bank_account_out
        ? String(props.accountPayable.bank_account_out)
        : ("" as string | number),
    observations: props.accountPayable.observations || "",
    receipt: props.accountPayable.receipt || "",
    value:
        props.accountPayable.installments?.[0]?.value !== undefined
            ? maskCurrency(String(props.accountPayable.installments[0].value))
            : props.accountPayable.total !== undefined
              ? maskCurrency(String(props.accountPayable.total))
              : "",
    due_date: props.accountPayable.installments?.[0]?.due_date
        ? props.accountPayable.installments[0].due_date.split("T")[0]
        : props.accountPayable.due_date
          ? props.accountPayable.due_date.split("T")[0]
          : "",
    status:
        props.accountPayable.installments?.[0]?.status ||
        props.accountPayable.status ||
        "open",
    installments: [] as any[], // individual installments payload (will be populated on form submit if needed)
});

const initialContact = props.accountPayable.financial_contact?.contact || null;

const backUrl = ref(route("tenant.finance.accounts-payable.list"));
const submitUrl = ref(
    route("tenant.finance.accounts-payable.update", props.accountPayable.id),
);

onMounted(() => {
    if (window.location.search) {
        backUrl.value =
            route("tenant.finance.accounts-payable.list") +
            window.location.search;
        submitUrl.value =
            route(
                "tenant.finance.accounts-payable.update",
                props.accountPayable.id,
            ) + window.location.search;
    }
});

function submit() {
    const payload = {
        ...form.data(),
        financial_category_id: form.financial_category_id
            ? Number(form.financial_category_id)
            : null,
        financial_subcategory_id: form.financial_subcategory_id
            ? Number(form.financial_subcategory_id)
            : null,
        cost_id: form.cost_id ? Number(form.cost_id) : null,
        bank_account_id: form.bank_account_id
            ? Number(form.bank_account_id)
            : null,
        financial_contact_id: form.financial_contact_id
            ? Number(form.financial_contact_id)
            : null,
        total: parseCurrencyToCents(form.total as string),
        value: parseCurrencyToCents(form.value as string),
        payment_method: form.payment_method || null,
        bank_account_out: form.bank_account_out
            ? Number(form.bank_account_out)
            : null,
    };

    form.transform(() => payload).put(submitUrl.value);
}
</script>

<template>
    <Head title="Editar Lançamento - Contas a Pagar" />

    <div
        class="mb-6 flex flex-col gap-4 border-b border-border pb-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Lançamento
            </h2>
            <p class="text-sm text-muted-foreground">
                Atualize as informações deste lançamento de contas a pagar.
            </p>
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
            :account-payable="props.accountPayable"
            :initial-contact="initialContact"
            @submit="submit"
            submitText="Atualizar Lançamento"
        />
    </div>
</template>
