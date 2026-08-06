<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { X, Folder, ChevronRight, Loader2, ArrowRight } from "lucide-vue-next";

interface DriveFolder {
    id: number;
    name: string;
    parent_folder_id?: number | null;
    sub_folders?: DriveFolder[];
}

interface DriveItem {
    id: number;
    document_type: "file" | "folder";
    drive_folder_id?: number | null;
}

const props = defineProps<{
    isOpen: boolean;
    itemsToMove: DriveItem[];
    availableFolders: DriveFolder[];
    isLoadingFolders?: boolean;
    isProcessing?: boolean;
}>();

const emit = defineEmits<{
    (e: "update:isOpen", value: boolean): void;
    (e: "move", targetFolderId: number | null): void;
}>();

const selectedFolderId = ref<number | null>(null);

// Resetar seleção ao abrir
watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            selectedFolderId.value = null;
        }
    }
);

// Verifica se existem arquivos na lista a ser movida
const hasFiles = computed(() => {
    return props.itemsToMove.some((item) => item.document_type === "file");
});

// Achata as pastas em uma lista hierárquica com indentação
const folderTree = computed(() => {
    const list: { id: number; name: string; level: number }[] = [];

    // IDs das pastas sendo movidas (para não permitir mover para si mesma ou subpastas)
    const movingFolderIds = new Set(
        props.itemsToMove
            .filter((item) => item.document_type === "folder")
            .map((item) => item.drive_folder_id || item.id)
    );

    function traverse(folders: DriveFolder[], level = 0) {
        for (const folder of folders) {
            // Ignora a pasta se ela estiver sendo movida
            if (movingFolderIds.has(folder.id)) continue;

            list.push({
                id: folder.id,
                name: folder.name,
                level,
            });

            if (folder.sub_folders && folder.sub_folders.length > 0) {
                traverse(folder.sub_folders, level + 1);
            }
        }
    }

    traverse(props.availableFolders);
    return list;
});

function handleClose() {
    emit("update:isOpen", false);
}

function handleMove() {
    if (selectedFolderId.value !== null) {
        // Se escolheu 0 (Raiz), passa null para a API
        const targetId = selectedFolderId.value === 0 ? null : selectedFolderId.value;
        emit("move", targetId);
    }
}
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
        @click.self="handleClose"
    >
        <div
            class="flex w-full max-w-md flex-col rounded-xl border border-border bg-card text-card-foreground shadow-2xl animate-in fade-in zoom-in-95 duration-200"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-border p-4"
            >
                <div class="flex flex-col gap-1">
                    <h3 class="text-base font-bold text-foreground">
                        Mover {{ itemsToMove.length > 1 ? `${itemsToMove.length} itens` : 'item' }}
                    </h3>
                    <p class="text-xs text-muted-foreground font-medium">
                        Selecione a pasta de destino para a movimentação
                    </p>
                </div>
                <button
                    @click="handleClose"
                    class="rounded-lg p-1 text-muted-foreground hover:bg-muted hover:text-foreground transition-colors cursor-pointer"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Content -->
            <div class="p-4 max-h-[320px] overflow-y-auto space-y-2">
                <!-- Estado Carregando -->
                <div
                    v-if="isLoadingFolders"
                    class="flex flex-col items-center justify-center p-8 text-muted-foreground"
                >
                    <Loader2 class="h-6 w-6 animate-spin text-primary mb-2" />
                    <span class="text-xs font-semibold">Buscando pastas de destino...</span>
                </div>

                <!-- Seletor de Pastas -->
                <div
                    v-else
                    class="divide-y divide-border border border-border rounded-lg overflow-hidden bg-background"
                >
                    <!-- Opção Meu Drive (Raiz) - Exibida apenas se não houver arquivos selecionados -->
                    <div
                        v-if="!hasFiles"
                        @click="selectedFolderId = 0"
                        class="flex cursor-pointer items-center justify-between p-3 transition-colors hover:bg-muted/50"
                        :class="[
                            selectedFolderId === 0
                                ? 'bg-primary/10 text-primary font-semibold'
                                : 'text-foreground',
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <Folder class="h-4.5 w-4.5 text-primary fill-primary/20" />
                            <span class="text-xs font-bold">
                                Meu Drive (Raiz)
                            </span>
                        </div>

                        <div class="h-4 w-4 rounded-full border border-input flex items-center justify-center"
                            :class="{ 'border-primary': selectedFolderId === 0 }"
                        >
                            <div
                                v-if="selectedFolderId === 0"
                                class="h-2 w-2 rounded-full bg-primary"
                            ></div>
                        </div>
                    </div>

                    <!-- Lista Hierárquica de Pastas -->
                    <div
                        v-for="folder in folderTree"
                        :key="folder.id"
                        @click="selectedFolderId = folder.id"
                        class="flex cursor-pointer items-center justify-between p-3 transition-colors hover:bg-muted/50"
                        :class="[
                            selectedFolderId === folder.id
                                ? 'bg-primary/10 text-primary font-semibold'
                                : 'text-foreground',
                        ]"
                    >
                        <div
                            class="flex items-center gap-2"
                            :style="{ paddingLeft: `${folder.level * 16}px` }"
                        >
                            <ChevronRight
                                v-if="folder.level > 0"
                                class="h-3.5 w-3.5 opacity-60"
                            />
                            <Folder class="h-4.5 w-4.5 text-primary fill-primary/20" />
                            <span class="text-xs font-semibold truncate max-w-[200px]">
                                {{ folder.name }}
                            </span>
                        </div>

                        <div class="h-4 w-4 rounded-full border border-input flex items-center justify-center"
                            :class="{ 'border-primary': selectedFolderId === folder.id }"
                        >
                            <div
                                v-if="selectedFolderId === folder.id"
                                class="h-2 w-2 rounded-full bg-primary"
                            ></div>
                        </div>
                    </div>

                    <!-- Estado vazio -->
                    <div
                        v-if="folderTree.length === 0 && hasFiles"
                        class="flex flex-col items-center justify-center p-6 text-center text-muted-foreground"
                    >
                        <Folder class="h-8 w-8 text-muted-foreground/50 mb-2 stroke-[1.5]" />
                        <span class="text-xs font-medium">Nenhuma pasta destino disponível</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-2 border-t border-border p-4">
                <button
                    @click="handleClose"
                    class="cursor-pointer rounded-lg border border-input px-4 py-2 text-xs font-semibold text-foreground hover:bg-muted transition-colors"
                    :disabled="isProcessing"
                >
                    Cancelar
                </button>
                <button
                    @click="handleMove"
                    class="flex cursor-pointer items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground hover:bg-primary/90 transition-colors"
                    :disabled="selectedFolderId === null || isProcessing || isLoadingFolders"
                >
                    <Loader2 v-if="isProcessing" class="h-3.5 w-3.5 animate-spin" />
                    <ArrowRight v-else class="h-3.5 w-3.5" />
                    Mover aqui
                </button>
            </div>
        </div>
    </div>
</template>
