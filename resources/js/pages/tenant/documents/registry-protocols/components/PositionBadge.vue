<script setup lang="ts">
import type { RegistryProtocolPositionOption } from "@/types";

defineProps<{
    position: RegistryProtocolPositionOption;
}>();

const badgeColors: Record<string, string> = {
    blue: "bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200",
    amber: "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200",
    orange: "bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-200",
    green: "bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200",
    emerald: "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200",
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
        :class="badgeColors[position.color] ?? 'bg-muted text-muted-foreground'"
    >
        {{ position.label }}
    </span>
</template>
