<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import RoleForm from "../components/RoleForm.vue";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    role: {
        id: number;
        name: string;
        permissions: string[];
    }
}>();

const form = useForm({
    name: props.role.name,
    permissions: props.role.permissions,
});

function submit() {
    console.log("Atualizando dados do formulário:", form.data());
    // form.put(route('tenant.settings.roles.update', props.role.id))
}
</script>

<template>
    <Head title="Editar Cargo" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Cargo: {{ role.name }}
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
