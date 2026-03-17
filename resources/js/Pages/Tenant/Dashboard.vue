<script setup>
import { useTenant } from '@/Composables/useTenant'
import { Link } from '@inertiajs/vue3'

const { tenant } = useTenant()
</script>

<template>
    <div>
        <h1>Bem-vindo, {{ tenant.name }}</h1>
        <Link href="/logout" view-transition>Sair</Link>
    </div>
</template>
