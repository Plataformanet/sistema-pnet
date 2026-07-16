<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { X, Folder, ChevronRight, Loader2, ArrowRight } from "lucide-vue-next";
import axios from "axios";
import { route } from "ziggy-js";
import { toast } from "vue-sonner";

interface FolderItem {
    id: number;
    name: string;
    parent_id: number | null;
}

interface MoveItem {
    id: number;
    name: string;
    type: "file" | "folder";
}

interface FolderNode extends FolderItem {
    level: number;
}

const props = defineProps<{
    isOpen: boolean;
    itemsToMove: MoveItem[];
}>();

const emit = defineEmits<{
    (e: "update:isOpen", val: boolean): void;
    (e: "saved"): void;
}>();

const selectedFolderId = ref<number | null>(null);
const isProcessing = ref(false);
const allFoldersList = ref<FolderItem[]>([]);
const isLoadingFolders = ref(false);

// Dispara a busca de todas as pastas do tenant de forma dinâmica assim que o modal abre
watch(
    () => props.isOpen,
    async (newVal) => {
        if (newVal) {
            selectedFolderId.value = null;
            isLoadingFolders.value = true;
            try {
                const res = await axios.get(route("tenant.drive.folders.list"));
                allFoldersList.value = res.data || [];
            } catch (err) {
                console.error("Erro ao carregar pastas:", err);
                toast.error("Falha ao carregar a lista de pastas de destino.");
            } finally {
                isLoadingFolders.value = false;
            }
        }
    }
);

// Verifica se há algum arquivo na lista de itens sendo movidos
const hasFiles = computed(() => {
    return props.itemsToMove.some((item) => item.type === "file");
});

// Helper para verificar se a pasta destino é descendente de uma pasta que está sendo movida
function isFolderChildOf(folderId: number, parentFolderId: number): boolean {
    const folder = allFoldersList.value.find((f) => f.id === folderId);
    if (!folder || !folder.parent_id) return false;
    if (folder.parent_id === parentFolderId) return true;
    return isFolderChildOf(folder.parent_id, parentFolderId);
}

// Filtra e reconstrói as pastas de forma ordenada e hierárquica usando a listagem assíncrona
const folderTree = computed(() => {
    const list: FolderNode[] = [];

    function build(parentId: number | null, level: number) {
        const children = allFoldersList.value.filter((f) => f.parent_id === parentId);
        children.forEach((folder) => {
            // Ignora a pasta de origem e qualquer subpasta filha dela
            const isInvalidDestination = props.itemsToMove.some((item) => {
                if (item.type === "folder") {
                    return (
                        folder.id === item.id ||
                        isFolderChildOf(folder.id, item.id)
                    );
                }
                return false;
            });

            if (!isInvalidDestination) {
                list.push({ ...folder, level });
                build(folder.id, level + 1);
            }
        });
    }

    build(null, 0);
    return list;
});

function handleClose() {
    selectedFolderId.value = null;
    emit("update:isOpen", false);
}

async function handleMove() {
    if (selectedFolderId.value === null) {
        toast.error("Selecione uma pasta de destino.");
        return;
    }

    isProcessing.value = true;

    try {
        const payload = {
            items: props.itemsToMove.map((item) => ({
                id: item.id,
                type: item.type,
            })),
            destination_folder_id: selectedFolderId.value,
        };

        await axios.post(route("tenant.drive.move"), payload);

        toast.success("Itens movidos com sucesso!");
        emit("saved");
        handleClose();
    } catch (err: any) {
        console.error("Erro ao mover itens:", err);
        const errorMsg =
            err.response?.data?.errors?.error?.[0] ||
            err.response?.data?.message ||
            "Erro ao mover os itens.";
        toast.error(errorMsg);
    } finally {
        isProcessing.value = false;
    }
}
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs"
        @click.self="handleClose"
    >
        <div
            class="flex w-full max-w-md flex-col rounded-xl bg-white shadow-2xl animate-in fade-in zoom-in-95 duration-200"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-slate-100 p-4"
            >
                <div class="flex flex-col gap-1">
                    <h3 class="text-base font-bold text-slate-800">
                        Mover {{ itemsToMove.length > 1 ? `${itemsToMove.length} itens` : 'item' }}
                    </h3>
                    <p class="text-xs text-slate-500 font-medium">
                        Selecione a pasta de destino para a movimentação
                    </p>
                </div>
                <button
                    @click="handleClose"
                    class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Content -->
            <div class="p-4 max-h-[320px] overflow-y-auto space-y-2">
                <!-- Estado Carregando -->
                <div
                    v-if="isLoadingFolders"
                    class="flex flex-col items-center justify-center p-8 text-slate-400"
                >
                    <Loader2 class="h-6 w-6 animate-spin text-indigo-500 mb-2" />
                    <span class="text-xs font-semibold">Buscando pastas de destino...</span>
                </div>

                <!-- Seletor de Pastas -->
                <div
                    v-else
                    class="divide-y divide-slate-50 border border-slate-100 rounded-lg overflow-hidden"
                >
                    <!-- Opção Meu Drive (Raiz) - Exibida apenas se não houver arquivos selecionados -->
                    <div
                        v-if="!hasFiles"
                        @click="selectedFolderId = 0"
                        class="flex cursor-pointer items-center justify-between p-3 transition-colors hover:bg-slate-50/70"
                        :class="[
                            selectedFolderId === 0
                                ? 'bg-indigo-50/80 hover:bg-indigo-50/80 text-indigo-700'
                                : 'text-slate-700',
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <Folder class="h-4.5 w-4.5 text-indigo-600 fill-indigo-100" />
                            <span class="text-xs font-bold">
                                Meu Drive (Raiz)
                            </span>
                        </div>

                        <div class="h-4 w-4 rounded-full border border-slate-300 flex items-center justify-center"
                            :class="{ 'border-indigo-600': selectedFolderId === 0 }"
                        >
                            <div
                                v-if="selectedFolderId === 0"
                                class="h-2 w-2 rounded-full bg-indigo-600"
                            ></div>
                        </div>
                    </div>

                    <!-- Lista Hierárquica de Pastas -->
                    <div
                        v-for="folder in folderTree"
                        :key="folder.id"
                        @click="selectedFolderId = folder.id"
                        class="flex cursor-pointer items-center justify-between p-3 transition-colors hover:bg-slate-50/70"
                        :class="[
                            selectedFolderId === folder.id
                                ? 'bg-indigo-50/80 hover:bg-indigo-50/80 text-indigo-700'
                                : 'text-slate-700',
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
                            <Folder class="h-4.5 w-4.5 text-indigo-500 fill-indigo-100" />
                            <span class="text-xs font-semibold truncate max-w-[200px]">
                                {{ folder.name }}
                            </span>
                        </div>

                        <div class="h-4 w-4 rounded-full border border-slate-300 flex items-center justify-center"
                            :class="{ 'border-indigo-600': selectedFolderId === folder.id }"
                        >
                            <div
                                v-if="selectedFolderId === folder.id"
                                class="h-2 w-2 rounded-full bg-indigo-600"
                            ></div>
                        </div>
                    </div>

                    <!-- Estado vazio -->
                    <div
                        v-if="folderTree.length === 0 && hasFiles"
                        class="flex flex-col items-center justify-center p-6 text-center text-slate-400"
                    >
                        <Folder class="h-8 w-8 text-slate-300 mb-2 stroke-[1.5]" />
                        <span class="text-xs font-medium">Nenhuma pasta destino disponível</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 p-4">
                <button
                    @click="handleClose"
                    class="cursor-pointer rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                    :disabled="isProcessing"
                >
                    Cancelar
                </button>
                <button
                    @click="handleMove"
                    class="flex cursor-pointer items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition-colors"
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
