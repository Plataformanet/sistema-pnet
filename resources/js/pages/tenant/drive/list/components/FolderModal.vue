<script setup lang="ts">
import { ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { X, Folder, Edit2, Loader2 } from "lucide-vue-next";
import type { Drive } from "@/types";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { toast } from "vue-sonner";

const props = defineProps<{
    isOpen: boolean;
    mode: "create" | "rename";
    item?: Drive | null;
    currentFolderId?: string | number | null;
}>();

const emit = defineEmits<{
    (e: "update:isOpen", val: boolean): void;
    (e: "saved"): void;
}>();

const name = ref("");
const isProcessing = ref(false);

// Sincroniza o valor inicial do input
watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            if (props.mode === "rename" && props.item) {
                name.value = props.item.name;
            } else {
                name.value = "";
            }
        }
    }
);

function handleSubmit() {
    if (!name.value.trim()) {
        toast.error("O nome não pode ser vazio.");
        return;
    }

    isProcessing.value = true;

    if (props.mode === "create") {
        router.post(
            route("tenant.drive.folders.store"),
            {
                name: name.value,
                parent_id: props.currentFolderId,
            },
            {
                onSuccess: () => {
                    emit("update:isOpen", false);
                    toast.success("Pasta criada com sucesso!");
                    emit("saved");
                },
                onError: (errors) => {
                    toast.error(errors.name || "Erro ao criar pasta.");
                },
                onFinish: () => {
                    isProcessing.value = false;
                }
            }
        );
    } else {
        if (!props.item) {
            isProcessing.value = false;
            return;
        }

        router.put(
            route("tenant.drive.update"),
            {
                id: props.item.id,
                name: name.value,
                type_drive: props.item.document_type === "folder" ? 1 : 2,
                drive_type:
                    props.item.document_type === "folder"
                        ? "folder"
                        : props.item.document_type,
            },
            {
                onSuccess: () => {
                    emit("update:isOpen", false);
                    toast.success("Item renomeado com sucesso!");
                    emit("saved");
                },
                onError: (errors) => {
                    toast.error(errors.name || "Erro ao renomear o item.");
                },
                onFinish: () => {
                    isProcessing.value = false;
                }
            }
        );
    }
}
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs"
    >
        <div
            class="w-full max-w-md animate-in overflow-hidden rounded-xl border border-slate-100 bg-white shadow-xl duration-200 zoom-in-95 fade-in"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-6 py-4"
            >
                <h3 class="flex items-center gap-2 font-bold text-slate-800">
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
                    class="text-slate-400 hover:text-slate-600 cursor-pointer"
                    :disabled="isProcessing"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Body -->
            <div class="space-y-4 p-6">
                <div class="space-y-1">
                    <label
                        class="text-xs font-semibold tracking-wider text-slate-500 uppercase"
                    >
                        {{ mode === 'create' ? 'Nome da Pasta' : 'Novo Nome' }}
                    </label>
                    <Input
                        v-model="name"
                        placeholder="Digite o nome..."
                        class="focus-visible:ring-indigo-500"
                        :disabled="isProcessing"
                        @keyup.enter="handleSubmit"
                    />
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4"
            >
                <Button
                    @click="emit('update:isOpen', false)"
                    variant="ghost"
                    class="text-slate-600 cursor-pointer"
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
