<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import SupplierForm from "../components/SupplierForm.vue";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    supplier: any;
}>();

const form = useForm({
    type: props.supplier.type,
    name: props.supplier.name ?? "",
    cpf: props.supplier.cpf ?? "",
    corporate_reason: props.supplier.corporate_reason ?? "",
    fantasy_name: props.supplier.fantasy_name ?? "",
    cnpj: props.supplier.cnpj ?? "",
    contact_name: props.supplier.contact_name ?? "",
    category: props.supplier.category ?? "",
    email: props.supplier.email ?? "",
    phone: props.supplier.phone ?? "",
    cellphone: props.supplier.cellphone ?? "",
    zipcode: props.supplier.zipcode ?? "",
    street: props.supplier.street ?? "",
    number: props.supplier.number ?? "",
    complement: props.supplier.complement ?? "",
    neighborhood: props.supplier.neighborhood ?? "",
    city: props.supplier.city ?? "",
    state: props.supplier.state ?? "",
});

function submit() {
    form.put(route("tenant.registrations.suppliers.update", props.supplier.id));
}
</script>

<template>
    <Head title="Editar Fornecedor" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
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
            @submit="submit"
        />
    </div>
</template>
