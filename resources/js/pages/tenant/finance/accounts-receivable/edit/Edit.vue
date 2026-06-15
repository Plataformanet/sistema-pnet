<script setup lang="ts">
import { onMounted, ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import { maskCurrency, parseCurrencyToCents } from "@/lib/masks";
import AccountReceivableForm from "../components/AccountReceivableForm.vue";
import {
    AccountReceivable,
    FinanceCategory,
    Cost,
    Contact,
    BankAccount,
} from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    accountReceivable: AccountReceivable;
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
    financial_category_id: props.accountReceivable.financial_category_id
        ? String(props.accountReceivable.financial_category_id)
        : "",
    financial_subcategory_id: props.accountReceivable.financial_subcategory_id
        ? String(props.accountReceivable.financial_subcategory_id)
        : "",
    cost_id: props.accountReceivable.cost_id
        ? String(props.accountReceivable.cost_id)
        : "",
    bank_account_id: props.accountReceivable.bank_account_id
        ? String(props.accountReceivable.bank_account_id)
        : "",
    financial_contact_id: props.accountReceivable.financial_contact?.contact_id
        ? String(props.accountReceivable.financial_contact.contact_id)
        : "",
    description: props.accountReceivable.description || "",
    total:
        props.accountReceivable.total !== undefined &&
        props.accountReceivable.total !== null
            ? maskCurrency(String(props.accountReceivable.total))
            : "",
    payment_method: normalizePaymentMethod(
        props.accountReceivable.payment_method,
    ) as string | number,
    payment_condition: props.accountReceivable.payment_condition || "",
    total_installments: props.accountReceivable.total_installments || 1,
    bank_account_out: props.accountReceivable.bank_account_out
        ? String(props.accountReceivable.bank_account_out)
        : ("" as string | number),
    observations: props.accountReceivable.observations || "",
    receipt: props.accountReceivable.receipt || "",
    value:
        props.accountReceivable.installments?.[0]?.value !== undefined
            ? maskCurrency(
                  String(props.accountReceivable.installments[0].value),
              )
            : props.accountReceivable.total !== undefined
              ? maskCurrency(String(props.accountReceivable.total))
              : "",
    due_date: props.accountReceivable.installments?.[0]?.due_date
        ? props.accountReceivable.installments[0].due_date.split("T")[0]
        : props.accountReceivable.due_date
          ? props.accountReceivable.due_date.split("T")[0]
          : "",
    status:
        props.accountReceivable.installments?.[0]?.status ||
        props.accountReceivable.status ||
        "open",
    installments: [] as any[], // individual installments payload (will be populated on form submit if needed)
});

const initialContact =
    props.accountReceivable.financial_contact?.contact || null;

const backUrl = ref(route("tenant.finance.accounts-receivable.list"));
const submitUrl = ref(
    route(
        "tenant.finance.accounts-receivable.update",
        props.accountReceivable.id,
    ),
);

onMounted(() => {
    if (window.location.search) {
        backUrl.value =
            route("tenant.finance.accounts-receivable.list") +
            window.location.search;
        submitUrl.value =
            route(
                "tenant.finance.accounts-receivable.update",
                props.accountReceivable.id,
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
    <Head title="Editar Lançamento - Contas a Receber" />

    <div
        class="mb-6 flex flex-col gap-4 border-b border-border pb-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Lançamento
            </h2>
            <p class="text-sm text-muted-foreground">
                Atualize as informações deste lançamento de contas a receber.
            </p>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="backUrl">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <AccountReceivableForm
            :form="form"
            :financial-categories="props.financialCategories"
            :financial-subcategories="props.financialSubcategories"
            :costs="props.costs"
            :contacts="props.contacts"
            :payment-conditions="props.paymentConditions"
            :bank-accounts="props.bankAccounts"
            :account-receivable="props.accountReceivable"
            :initial-contact="initialContact"
            @submit="submit"
            submitText="Atualizar Lançamento"
        />
    </div>
</template>
