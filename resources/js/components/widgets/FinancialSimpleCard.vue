<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { ArrowUpRight, ArrowDownRight } from "lucide-vue-next";
import type { Component } from "vue";

interface Props {
    title: string;
    value: string;
    percentage: string;
    percentageType: "up" | "down";
    isGreen: boolean;
    icon?: Component;
    comparisonText: string;
}

const props = defineProps<Props>();
</script>

<template>
    <Card
        class="group relative overflow-hidden transition-all hover:border-primary/50 hover:shadow-md"
    >
        <!-- Efeito de brilho de fundo opcional -->
        <!-- <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-primary/5 opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div> -->

        <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
        >
            <CardTitle class="text-sm font-medium text-muted-foreground">
                {{ props.title }}
            </CardTitle>

            <div
                v-if="props.icon"
                class="flex h-8 w-8 items-center justify-center rounded-md bg-primary/10 text-primary"
            >
                <component :is="props.icon" class="h-4 w-4" />
            </div>
        </CardHeader>

        <CardContent>
            <div class="text-2xl font-bold tracking-tight">
                {{ props.value }}
            </div>
            <div class="mt-1 flex items-center text-xs text-muted-foreground">
                <span
                    class="mr-1 flex items-center font-medium"
                    :class="props.isGreen ? 'text-emerald-500' : 'text-red-500'"
                >
                    <template v-if="props.percentageType === 'up'">
                        <ArrowUpRight class="mr-1 h-3 w-3" />
                    </template>
                    <template v-else>
                        <ArrowDownRight class="mr-1 h-3 w-3" />
                    </template>
                    {{ props.percentage }}
                </span>
                <span>{{ props.comparisonText }}</span>
            </div>
        </CardContent>
    </Card>
</template>
