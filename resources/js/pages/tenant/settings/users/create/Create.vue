<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import UserForm from "../components/UserForm.vue";

defineOptions({ layout: TenantLayout });

const form = useForm({
    name: "",
    email: "",
    role: "",
    status: true,
    password: "",
    password_confirmation: "",
});

defineProps<{ roles: string[] }>();

function submit() {
    form.post(route('tenant.settings.users.store'))
}
</script>

<template>
    <Head title="Novo Usuário" />

    <div
        class="mb-6 flex items-center justify-between border-b border-border pb-4"
    >
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Novo Usuário
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.settings.users.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <UserForm :form="form" :roles="roles" @submit="submit" />
    </div>
</template>
