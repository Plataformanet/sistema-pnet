<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import { parseCurrencyToCents } from "@/lib/masks";
import BillableServiceForm from "../components/BillableServiceForm.vue";

defineOptions({ layout: TenantLayout });

const form = useForm({
    name: "",
    description: "",
    price: "",
    generates_receipt: false,
});

function submit() {
    const payload = {
        ...form.data(),
        price: parseCurrencyToCents(form.price as string),
    };
    form.transform(() => payload).post(route('tenant.documents.billable-services.store'));
}
</script>

<template>
    <Head title="Novo Serviço" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Novo Serviço
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.billable-services.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <BillableServiceForm :form="form" @submit="submit" />
    </div>
</template>
