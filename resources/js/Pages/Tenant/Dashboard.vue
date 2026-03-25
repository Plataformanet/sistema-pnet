<script setup>
import { useTenant } from "@/Composables/useTenant";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";

defineOptions({ layout: TenantLayout });

const { tenant } = useTenant();
</script>

<template>
    <Head title="Dashboard" />
    <h1>Bem-vindo, {{ tenant.name }}</h1>
    <Link :href="route('tenant.logout')" view-transition>Sair</Link>
</template>
