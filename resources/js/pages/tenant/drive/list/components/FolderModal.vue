<script setup lang="ts">
import { ref, watch } from "vue";
import { X, Folder, Edit2, Loader2 } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

const props = defineProps<{
    isOpen: boolean;
    mode: "create" | "rename";
    initialName?: string;
    isProcessing?: boolean;
}>();

const emit = defineEmits<{
    (e: "update:isOpen", value: boolean): void;
    (e: "submit", name: string): void;
}>();

const name = ref(props.initialName || "");

watch(
    () => props.initialName,
    (val) => {
        name.value = val || "";
    }
);

watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            name.value = props.initialName || "";
        }
    }
);

function handleSubmit() {
    if (name.value.trim()) {
        emit("submit", name.value.trim());
    }
}
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
    >
        <div
            class="w-full max-w-md animate-in overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-xl duration-200 zoom-in-95 fade-in"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-border bg-muted/50 px-6 py-4"
            >
                <h3 class="flex items-center gap-2 font-bold text-foreground">
                    <Folder
                        v-if="mode === 'create'"
                        class="h-5 w-5 fill-amber-500 text-amber-500"
                    />
                    <Edit2
                        v-else
                        class="h-4.5 w-4.5 text-blue-600"
                    />
                    {{ mode === 'create' ? 'Criar Nova Pasta' : 'Renomear Item' }}
                </h3>
                <button
                    @click="emit('update:isOpen', false)"
                    class="text-muted-foreground hover:text-foreground cursor-pointer"
                    :disabled="isProcessing"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Body -->
            <div class="space-y-4 p-6">
                <div class="space-y-1">
                    <label
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        {{ mode === 'create' ? 'Nome da Pasta' : 'Novo Nome' }}
                    </label>
                    <Input
                        v-model="name"
                        placeholder="Digite o nome..."
                        class="focus-visible:ring-primary"
                        :disabled="isProcessing"
                        @keyup.enter="handleSubmit"
                    />
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex items-center justify-end gap-2 border-t border-border bg-muted/50 px-6 py-4"
            >
                <Button
                    @click="emit('update:isOpen', false)"
                    variant="ghost"
                    class="cursor-pointer"
                    :disabled="isProcessing"
                >
                    Cancelar
                </Button>
                <Button
                    @click="handleSubmit"
                    class="cursor-pointer font-semibold"
                    :disabled="isProcessing"
                >
                    <Loader2
                        v-if="isProcessing"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    {{ mode === 'create' ? 'Criar Pasta' : 'Salvar' }}
                </Button>
            </div>
        </div>
    </div>
</template>
