<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import { maskCPF, maskPhone } from "@/lib/masks";
import QuoteForm from "../components/QuoteForm.vue";
import type { BillableServiceOption, Option, QuoteDetail, ServiceSelection } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    quote: QuoteDetail;
    banks: Array<{ id: number; name: string }>;
    services: BillableServiceOption[];
    maritalStatuses: Option<number>[];
}>();

const form = useForm({
    name: props.quote.name,
    cpf: maskCPF(props.quote.cpf),
    email: props.quote.email,
    phone: maskPhone(props.quote.phone),
    profession: props.quote.profession,
    marital_status: props.quote.marital_status,
    bank_id: props.quote.bank_id,
    valid_until: props.quote.valid_until ?? "",
    services: props.quote.services.map((service) => ({ id: service.id, amount: service.amount })) as ServiceSelection[],
});

function submit() {
    form.put(route("tenant.documents.quotes.update", props.quote.id));
}
</script>

<template>
    <Head :title="`Editar Orçamento Nº ${quote.number}`" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <h2 class="text-3xl font-bold tracking-tight text-foreground">Editar Orçamento Nº {{ quote.number }}</h2>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.quotes.show', quote.id)"><ChevronLeft class="mr-2 h-4 w-4" /> Voltar</Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl">
        <QuoteForm :form="form" :banks="banks" :services="services" :marital-statuses="maritalStatuses" submit-text="Atualizar Orçamento" @submit="submit" />
    </div>
</template>
