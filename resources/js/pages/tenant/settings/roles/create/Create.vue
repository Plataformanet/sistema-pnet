<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import RoleForm from "../components/RoleForm.vue";

defineOptions({ layout: TenantLayout });

const form = useForm({
    name: "",
    permissions: [] as string[],
});

function submit() {
    console.log("Enviando dados do formulário:", form.data());
    // form.post(route('tenant.settings.roles.store'))
}
</script>

<template>
    <Head title="Novo Cargo" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Novo Cargo
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.settings.roles.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <RoleForm :form="form" @submit="submit" />
    </div>
</template>
