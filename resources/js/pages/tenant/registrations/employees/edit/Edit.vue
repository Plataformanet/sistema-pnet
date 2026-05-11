<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import EmployeeForm from "../components/EmployeeForm.vue";

import { Employee } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    employee: Employee;
}>();

const form = useForm({
    name_corporatereason: props.employee.contact?.name_corporatereason ?? "",
    cpf_cnpj: props.employee.contact?.cpf_cnpj ?? "",
    rg: props.employee.rg ?? "",
    birth_date: props.employee.birth_date ?? "",
    position: props.employee.position ?? "",
    salary: props.employee.salary ?? "",
    hire_date: props.employee.hire_date ?? "",
    email: props.employee.contact?.email ?? "",
    phone: props.employee.contact?.phone ?? "",
    cell_phone: props.employee.contact?.cell_phone ?? "",
    zip_code: props.employee.contact?.address?.zip_code ?? "",
    street: props.employee.contact?.address?.street ?? "",
    number: props.employee.contact?.address?.number ?? "",
    complement: props.employee.contact?.address?.complement ?? "",
    neighborhood: props.employee.contact?.address?.neighborhood ?? "",
    city: props.employee.contact?.address?.city ?? "",
    state: props.employee.contact?.address?.state ?? "",
});

function submit() {
    form.put(route("tenant.registrations.employees.update", props.employee.contact?.id));
}
</script>

<template>
    <Head title="Editar Funcionário" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Funcionário
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.registrations.employees.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <EmployeeForm
            :form="form"
            submitText="Atualizar Funcionário"
            @submit="submit"
        />
    </div>
</template>
