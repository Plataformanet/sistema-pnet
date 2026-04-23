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
    name: props.employee.name ?? "",
    cpf: props.employee.cpf ?? "",
    rg: props.employee.rg ?? "",
    birth_date: props.employee.birth_date ?? "",
    position: props.employee.position ?? "",
    salary: props.employee.salary ?? "",
    hire_date: props.employee.hire_date ?? "",
    email: props.employee.email ?? "",
    phone: props.employee.phone ?? "",
    cellphone: props.employee.cellphone ?? "",
    zipcode: props.employee.zipcode ?? "",
    street: props.employee.street ?? "",
    number: props.employee.number ?? "",
    complement: props.employee.complement ?? "",
    neighborhood: props.employee.neighborhood ?? "",
    city: props.employee.city ?? "",
    state: props.employee.state ?? "",
});

function submit() {
    form.put(route("tenant.registrations.employees.update", props.employee.id));
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
