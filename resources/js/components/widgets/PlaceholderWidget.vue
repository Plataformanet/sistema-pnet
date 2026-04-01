<script setup lang="ts"></script>
<template>
    <!-- Placeholder interativo simulando um "Drop Zone" do Drag and Drop -->
    <div
        class="rounded-xl border-2 border-dashed border-muted bg-muted/20 flex items-center justify-center p-12 text-center transition-colors hover:bg-muted/30"
    >
        <div class="flex flex-col items-center gap-3 text-muted-foreground">
            <svg
                class="h-10 w-10 text-muted-foreground/50"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                />
            </svg>
            <div class="space-y-1">
                <p class="text-sm font-medium">Arrastar e Soltar</p>
                <p class="text-xs">Solte um novo widget neste espaço vazio</p>
            </div>
        </div>
    </div>
</template>
