<script setup lang="ts">
import { Button } from "@/components/ui/button";
import { Download, Loader2, X } from "lucide-vue-next";
import type { ZipLimits } from "@/types";

defineProps<{
    count: number;
    limits: ZipLimits;
    downloading: boolean;
}>();

const emit = defineEmits<{
    download: [];
    clear: [];
}>();
</script>

<template>
    <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-primary/40 bg-primary/5 px-4 py-3 text-sm"
    >
        <div>
            <span class="font-medium">{{ count }} documento{{ count === 1 ? "" : "s" }} selecionado{{ count === 1 ? "" : "s" }}</span>
            <span v-if="count > limits.max" class="ml-2 text-red-600">
                Selecione no máximo {{ limits.max }} documentos.
            </span>
            <span v-else-if="count > limits.sync" class="ml-2 text-muted-foreground">
                Acima de {{ limits.sync }} documentos, o ZIP é gerado em segundo plano e enviado por e-mail.
            </span>
        </div>
        <div class="flex gap-2">
            <Button variant="ghost" size="sm" @click="emit('clear')"><X class="mr-1 h-4 w-4" /> Limpar seleção</Button>
            <Button size="sm" :disabled="downloading || count > limits.max" @click="emit('download')">
                <Loader2 v-if="downloading" class="mr-1 h-4 w-4 animate-spin" />
                <Download v-else class="mr-1 h-4 w-4" />
                Baixar selecionados (ZIP)
            </Button>
        </div>
    </div>
</template>
