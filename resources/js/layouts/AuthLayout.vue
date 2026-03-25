<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { cn } from "@/lib/utils";
import { Card, CardContent } from "@/components/ui/card";
import { HTMLAttributes } from "vue";

const props = defineProps<{
    title: string;
    class?: HTMLAttributes["class"];
}>();
</script>
<template>
    <Head :title="props.title" />
    <div
        class="bg-muted flex min-h-svh flex-col items-center justify-center p-6 md:p-10"
    >
        <div class="w-full max-w-sm md:max-w-4xl">
            <div :class="cn('flex flex-col gap-6', props.class)">
                <Card class="overflow-hidden p-0">
                    <CardContent class="grid p-0 md:grid-cols-2">
                        <slot />
                        <div
                            class="relative flex items-center justify-center bg-white border-t border-gray-200 md:border-l md:border-t-0"
                        >
                            <img
                                src="/images/logo-plataformanet-preto.png"
                                alt="Image"
                                class="h-auto w-full dark:brightness-[0.2] dark:grayscale"
                            />
                        </div>
                    </CardContent>
                </Card>
                <!-- <FieldDescription class="px-6 text-center">
      Ao continuar, você concorda com nossos <a href="#">Termos de serviço</a> e
      <a href="#">Política de privacidade</a>.
    </FieldDescription> -->
            </div>
        </div>
    </div>
</template>
