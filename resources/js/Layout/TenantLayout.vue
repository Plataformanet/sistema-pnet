<script setup>
import { useTenant } from '@/Composables/useTenant'
import { Link } from '@inertiajs/vue3'

const { tenant } = useTenant()
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 flex justify-between h-16 items-center">
                <span class="font-bold text-gray-800">
                    {{ tenant?.domain }}
                </span>

                <div class="flex gap-4">
                    <Link :href="route('dashboard')" class="text-gray-600 hover:text-gray-900">
                        Dashboard
                    </Link>
                    <Link :href="route('documents.index')" class="text-gray-600 hover:text-gray-900">
                        Documentos
                    </Link>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 px-4">
            <slot />
        </main>
    </div>
</template>
