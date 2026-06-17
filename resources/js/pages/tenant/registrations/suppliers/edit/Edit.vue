<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import SupplierForm from "../components/SupplierForm.vue";

import { Supplier } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    supplier: Supplier;
}>();

const form = useForm({
    type: props.supplier.contact?.type ?? "",
    name_corporatereason: props.supplier.contact?.name_corporatereason ?? "",
    cpf_cnpj: props.supplier.contact?.cpf_cnpj ?? "",
    fantasy_name: props.supplier.contact?.fantasy_name ?? "",
    responsible_person: props.supplier?.responsible_person ?? "",
    description: props.supplier?.description ?? "",
    // categories: [] as string[],
    supply_category: props.supplier?.supply_category ?? "",
    email: props.supplier.contact?.email ?? "",
    phone: props.supplier.contact?.phone ?? "",
    cell_phone: props.supplier.contact?.cell_phone ?? "",
    zip_code: props.supplier.contact?.address?.zip_code ?? "",
    street: props.supplier.contact?.address?.street ?? "",
    number: props.supplier.contact?.address?.number ?? "",
    complement: props.supplier.contact?.address?.complement ?? "",
    neighborhood: props.supplier.contact?.address?.neighborhood ?? "",
    city: props.supplier.contact?.address?.city ?? "",
    state: props.supplier.contact?.address?.state ?? "",
});

function submit() {
    form.put(route("tenant.registrations.suppliers.update", props.supplier.contact?.id));
}
</script>

<template>
    <Head title="Editar Fornecedor" />

    <div
        class="mb-6 flex items-center justify-between border-b border-border pb-4"
    >
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Fornecedor
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.registrations.suppliers.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <SupplierForm
            :form="form"
            submitText="Atualizar Fornecedor"
            :isEdit="true"
            @submit="submit"
        />
    </div>
</template>
