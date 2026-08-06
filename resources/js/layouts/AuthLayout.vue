<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { cn } from "@/lib/utils";
import { Card, CardContent } from "@/components/ui/card";
import { HTMLAttributes } from "vue";
import { useTenant } from "@/composables/useTenant";

const props = defineProps<{
    title: string;
    class?: HTMLAttributes["class"];
}>();

const { tenant } = useTenant();
</script>
<template>
    <Head :title="props.title" />
    <div
        class="flex min-h-svh flex-col items-center justify-center bg-muted p-6 md:p-10"
    >
        <div class="w-full max-w-sm md:max-w-4xl">
            <div :class="cn('flex flex-col gap-6', props.class)">
                <Card class="overflow-hidden p-0">
                    <CardContent class="grid p-0 md:grid-cols-2">
                        <slot />
                        <div
                            class="relative flex flex-col items-center justify-center border-t border-gray-200 bg-white p-6 md:border-t-0 md:border-l"
                        >
                            <img
                                v-if="tenant?.logoUrl"
                                :src="tenant.logoUrl"
                                :alt="tenant?.name || 'Logo da Empresa'"
                                class="max-h-48 max-w-full object-contain p-4"
                            />
                            <img
                                v-else
                                src="/images/logo-plataformanet-preto.png"
                                alt="PlataformaNet"
                                class="h-auto w-full dark:brightness-[0.2] dark:grayscale"
                            />
                            <p v-if="tenant?.name && tenant?.logoUrl" class="mt-2 text-sm font-medium text-muted-foreground text-center">
                                {{ tenant.name }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
