<script setup>
import { useTenant } from '@/Composables/useTenant'

const { tenant } = useTenant()
</script>

<template>
    <div>
        <h1>Bem-vindo, {{ tenant.name }}</h1>
    </div>
</template>
