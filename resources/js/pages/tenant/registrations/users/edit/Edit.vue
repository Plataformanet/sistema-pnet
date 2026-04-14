<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import UserForm from "../components/UserForm.vue";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    user: any;
}>();

const form = useForm({
    name: props.user.name ?? "",
    email: props.user.email ?? "",
    role: props.user.role ?? "",
    active: props.user.active ?? true,
    password: "",
    password_confirmation: "",
});

function submit() {
    form.put(route("tenant.registrations.users.update", props.user.id));
}
</script>

<template>
    <Head title="Editar Usuário" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Usuário
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.registrations.users.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <UserForm
            :form="form"
            submitText="Atualizar Usuário"
            @submit="submit"
        />
    </div>
</template>
