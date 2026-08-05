<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { route } from "ziggy-js";
import { toast } from "vue-sonner";
import axios from "axios";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Folder,
    Search,
    Plus,
    Upload,
    ArrowLeft,
    Share2,
    Edit2,
    Trash2,
    X,
    Check,
    Move,
} from "lucide-vue-next";
import type { Drive } from "@/types";
import { getFileIcon, getIconColorClass, formatSize } from "../utils/drive-helpers";

// Composables extraídos (Clean Architecture)
import { useDriveSelection } from "./composables/useDriveSelection";
import { useDriveDragDrop, type ItemToMove } from "./composables/useDriveDragDrop";

// Subcomponentes extraídos
import ShareModal from "./components/ShareModal.vue";
import FolderModal from "./components/FolderModal.vue";
import DeleteConfirmModal from "./components/DeleteConfirmModal.vue";
import MoveModal from "./components/MoveModal.vue";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    drives: Drive[];
    folders: { id: number; name: string; parent_id: number | null }[];
}>();

const page = usePage();

// Composable de Seleção Múltipla Avançada (Ctrl/Cmd + Click, Shift + Click)
const {
    selectedDrives,
    clearSelection,
    isSelected,
    handleRowClick,
    toggleSelectAll: executeToggleSelectAll,
} = useDriveSelection();

// Composable de Drag & Drop Interno (Mover Arquivos / Pastas)
const {
    activeDropTargetId,
    isDraggingInternal,
    handleDragStart,
    handleDragOverFolder,
    handleDragLeaveFolder,
    handleDropOnFolder,
    handleDragEnd,
} = useDriveDragDrop();

// Detecção de Dispositivos Touch (Mobile/Tablets)
const isTouchDevice = ref(false);

onMounted(() => {
    isTouchDevice.value = window.matchMedia("(pointer: coarse)").matches;
});

function onRowClick(
    event: MouseEvent,
    item: Drive,
    index: number,
    allDrives: Drive[]
) {
    if (item.permission_attrs?.disable) return;

    // Em telas de toque (mobile/tablet), o toque simples na linha de uma pasta abre o diretório
    if (isTouchDevice.value && item.document_type === "folder") {
        navigateToFolder(item);
        return;
    }

    handleRowClick(event, item, index, allDrives);
}

// Estados Reativos Principais
const searchQuery = ref("");
const isNewFolderModalOpen = ref(false);
const isRenameModalOpen = ref(false);
const renameItem = ref<Drive | null>(null);
const isShareModalOpen = ref(false);
const shareItem = ref<Drive | null>(null);
const isDeleteConfirmOpen = ref(false);
const itemToDelete = ref<Drive | null>(null);
const isDeletingBulk = ref(false);
const isMoveModalOpen = ref(false);
const itemsToMove = ref<{ id: number; name: string; type: "file" | "folder" }[]>([]);

interface UploadItem {
    id: string;
    name: string;
    progress: number;
    status: "pending" | "uploading" | "success" | "error";
}

// Upload de arquivos externos do SO
const fileInput = ref<HTMLInputElement | null>(null);
const uploadQueue = ref<UploadItem[]>([]);

// Computed para checar se há arquivos subindo
const isUploading = computed(() => {
    return uploadQueue.value.some((item) => item.status === "uploading");
});

// Contador de uploads ativos
const activeUploadsCount = computed(() => {
    return uploadQueue.value.filter((item) => item.status === "uploading").length;
});

function clearCompletedUploads() {
    uploadQueue.value = uploadQueue.value.filter((item) => item.status === "uploading");
}

// ID da pasta atual baseada na URL
const currentFolderId = computed(() => {
    return page.url.includes("folder_id=")
        ? new URLSearchParams(page.url.split("?")[1]).get("folder_id")
        : null;
});

// Computed para filtrar apenas os itens selecionáveis (que o usuário tem permissão)
const selectableDrives = computed(() => {
    return props.drives.filter((d) => !d.permission_attrs?.disable);
});

// Computed para checar se todos os itens selecionáveis estão selecionados
const isAllSelected = computed(() => {
    return (
        selectableDrives.value.length > 0 &&
        selectedDrives.value.length === selectableDrives.value.length
    );
});

function toggleSelectAll() {
    executeToggleSelectAll(selectableDrives.value);
}

// Executar movimentação via Drag & Drop ou Modal
function executeMoveItems(items: ItemToMove[], destinationFolderId: number) {
    const payload = {
        items: items.map((item) => ({
            id: item.id,
            type: item.type,
        })),
        destination_folder_id: destinationFolderId,
    };

    axios
        .post(route("tenant.drive.move"), payload)
        .then(() => {
            clearSelection();
            toast.success(
                items.length > 1
                    ? `${items.length} itens movidos com sucesso!`
                    : `"${items[0].name}" movido com sucesso!`
            );
            handleRefreshData();
        })
        .catch((err: any) => {
            const errorMsg =
                err.response?.data?.errors?.error?.[0] ||
                err.response?.data?.message ||
                "Erro ao mover os itens selecionados.";
            toast.error(errorMsg);
        });
}

// Ações do Drive
function handleSearch() {
    router.visit(route("tenant.drive.search"), {
        data: { query: searchQuery.value },
        preserveState: true,
        preserveScroll: true,
    });
}

function clearSearch() {
    searchQuery.value = "";
    router.visit(route("tenant.drive.index"));
}

// Navegar para subpasta
function navigateToFolder(item: Drive) {
    if (item.document_type !== "folder") return;
    clearSelection();
    router.visit(route("tenant.drive.index"), {
        data: { folder_id: item.drive_folder_id },
    });
}

function navigateToBreadcrumb(folderId: number | null) {
    clearSelection();
    if (!folderId) {
        router.visit(route("tenant.drive.index"));
    } else {
        router.visit(route("tenant.drive.index"), {
            data: { folder_id: folderId },
        });
    }
}

// Criar nova pasta
function openNewFolderModal() {
    isNewFolderModalOpen.value = true;
}

// Upload de arquivos
function triggerFileInput() {
    if (fileInput.value) {
        fileInput.value.click();
    }
}

function handleFileUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const files = Array.from(target.files);
        uploadFiles(files);
    }
}

async function uploadFiles(files: File[]) {
    const newItems: UploadItem[] = files.map((file) => ({
        id: Math.random().toString(36).substring(2, 9),
        name: file.name,
        progress: 0,
        status: "pending",
    }));

    uploadQueue.value = [...uploadQueue.value, ...newItems];

    const promises = files.map((file, index) => {
        const itemId = newItems[index].id;

        const formData = new FormData();
        formData.append("user_id", String((page.props as any).auth?.user?.id ?? ""));
        formData.append("folder_id", String(currentFolderId.value ?? ""));
        formData.append("documents[]", file);

        const queueItem = uploadQueue.value.find((item) => item.id === itemId);
        if (queueItem) {
            queueItem.status = "uploading";
        }

        const progressInterval = setInterval(() => {
            const item = uploadQueue.value.find((i) => i.id === itemId);
            if (item && item.progress < 90 && item.status === "uploading") {
                item.progress += Math.floor(Math.random() * 15) + 5;
                if (item.progress > 90) item.progress = 90;
            }
        }, 200);

        return axios
            .post(route("tenant.drive.store"), formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then(() => {
                clearInterval(progressInterval);
                const queueItem = uploadQueue.value.find((item) => item.id === itemId);
                if (queueItem) {
                    queueItem.progress = 100;
                    queueItem.status = "success";
                }
            })
            .catch((err) => {
                clearInterval(progressInterval);
                console.error("Erro ao subir arquivo:", file.name, err);
                const queueItem = uploadQueue.value.find((item) => item.id === itemId);
                if (queueItem) {
                    queueItem.status = "error";
                }
            });
    });

    await Promise.all(promises);

    router.reload({ only: ["drives"] });

    const totalSuccess = newItems.filter((i) => i.status === "success").length;
    const totalError = newItems.filter((i) => i.status === "error").length;

    if (totalSuccess > 0) {
        toast.success(
            totalSuccess > 1
                ? `${totalSuccess} arquivos enviados com sucesso!`
                : "Arquivo enviado com sucesso!"
        );
    }
    if (totalError > 0) {
        toast.error(
            totalError > 1
                ? `Falha no envio de ${totalError} arquivos.`
                : `Falha no envio de "${newItems.find((i) => i.status === "error")?.name}".`
        );
    }

    if (fileInput.value) {
        fileInput.value.value = "";
    }

    setTimeout(() => {
        uploadQueue.value = uploadQueue.value.filter(
            (item) => item.status !== "success"
        );
    }, 4000);
}

// Excluir item
function confirmDelete(item: Drive) {
    itemToDelete.value = item;
    isDeletingBulk.value = false;
    isDeleteConfirmOpen.value = true;
}

// Excluir selecionados em lote
function deleteSelectedDrives() {
    if (selectedDrives.value.length === 0) return;
    itemToDelete.value = null;
    isDeletingBulk.value = true;
    isDeleteConfirmOpen.value = true;
}

// Executar exclusão confirmada
function executeDelete() {
    isDeleteConfirmOpen.value = false;

    if (isDeletingBulk.value) {
        router.delete(route("tenant.drive.delete-selected"), {
            data: { selectedValues: selectedDrives.value },
            onSuccess: () => {
                clearSelection();
                toast.success("Itens movidos para a lixeira com sucesso!");
            },
            onError: () => toast.error("Erro ao excluir os itens selecionados."),
        });
    } else if (itemToDelete.value) {
        const item = itemToDelete.value;
        if (item.document_type === "folder") {
            router.delete(
                route("tenant.drive.folders.destroy", item.drive_folder_id),
                {
                    onSuccess: () => {
                        toast.success("Pasta movida para a lixeira!");
                        itemToDelete.value = null;
                    },
                    onError: () => toast.error("Erro ao excluir a pasta."),
                }
            );
        } else {
            router.delete(route("tenant.drive.destroy", item.id), {
                onSuccess: () => {
                    toast.success("Arquivo movido para a lixeira!");
                    itemToDelete.value = null;
                },
                onError: () => toast.error("Erro ao excluir o arquivo."),
            });
        }
    }
}

// Renomear item
function openRenameModal(item: Drive) {
    renameItem.value = item;
    isRenameModalOpen.value = true;
}

// Compartilhar / Permissões
function openShareModal(item: Drive) {
    shareItem.value = item;
    isShareModalOpen.value = true;
}

function openMoveModal(item: Drive) {
    itemsToMove.value = [
        {
            id: item.document_type === "folder" ? item.drive_folder_id! : item.id,
            name: item.name,
            type: item.document_type === "folder" ? "folder" : "file",
        },
    ];
    isMoveModalOpen.value = true;
}

function openBulkMoveModal() {
    if (selectedDrives.value.length === 0) return;

    itemsToMove.value = selectedDrives.value.map((id) => {
        const drive = props.drives.find((d) => d.id === id);
        return {
            id: drive?.document_type === "folder" ? drive.drive_folder_id! : id,
            name: drive?.name ?? "",
            type: drive?.document_type === "folder" ? ("folder" as const) : ("file" as const),
        };
    });
    isMoveModalOpen.value = true;
}

// Manipulação de Upload por Arraste de Arquivos do SO
const isDraggingExternal = ref(false);
const dragCounter = ref(0);

function handleDragEnter(event: DragEvent) {
    if (isDraggingInternal.value) return;
    if (!event.dataTransfer?.types.includes("Files")) return;

    event.preventDefault();
    dragCounter.value++;
    isDraggingExternal.value = true;
}

function handleDragLeave(event: DragEvent) {
    if (isDraggingInternal.value) return;
    event.preventDefault();
    dragCounter.value--;
    if (dragCounter.value === 0) {
        isDraggingExternal.value = false;
    }
}

function handleDragOver(event: DragEvent) {
    if (isDraggingInternal.value) return;
    if (!event.dataTransfer?.types.includes("Files")) return;
    event.preventDefault();
    isDraggingExternal.value = true;
}

function handleFileDrop(event: DragEvent) {
    if (isDraggingInternal.value) return;
    event.preventDefault();
    isDraggingExternal.value = false;
    dragCounter.value = 0;

    if (!event.dataTransfer?.files || event.dataTransfer.files.length === 0) return;

    const files = Array.from(event.dataTransfer.files);
    uploadFiles(files);
}

function handleRefreshData() {
    clearSelection();
    router.reload();
}
</script>

<template>
    <Head title="Meu Drive" />

    <div
        class="space-y-6 relative min-h-[calc(100vh-10rem)]"
        @dragenter="handleDragEnter"
        @dragover="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleFileDrop"
    >
        <!-- Overlay Visual de Drag & Drop Externo (Upload SO - Tela Cheia) -->
        <div
            v-if="isDraggingExternal"
            class="fixed inset-6 z-50 flex flex-col items-center justify-center border-4 border-dashed border-indigo-500 bg-white/90 p-6 backdrop-blur-sm transition-all duration-200 animate-in fade-in zoom-in-95 rounded-2xl shadow-2xl"
        >
            <div class="flex flex-col items-center gap-4 text-indigo-600">
                <Upload class="h-20 w-20 animate-bounce" />
                <h3 class="text-2xl font-bold">Solte seus arquivos aqui</h3>
                <p class="text-base text-slate-500 font-medium">Os arquivos serão carregados nesta pasta</p>
            </div>
        </div>

        <!-- Header da Página -->
        <div
            class="flex flex-col gap-4 border-b border-slate-100 pb-5 md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Gerenciador de Arquivos</h1>
                <p class="text-sm text-slate-500">
                    Gerencie, organize e compartilhe seus arquivos de forma segura.
                </p>
            </div>

            <!-- Busca de Arquivos -->
            <div class="flex items-center gap-3">
                <div class="relative w-full md:w-72">
                    <Search
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    />
                    <Input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Pesquisar arquivos..."
                        class="pl-9 pr-8"
                        @keyup.enter="handleSearch"
                    />
                    <button
                        v-if="searchQuery"
                        @click="clearSearch"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Navegação Breadcrumb (Com Suporte a Drop Zones para Mover) -->
        <div
            class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
        >
            <div class="flex items-center gap-1.5 text-sm">
                <!-- Raiz "Meu Drive" -->
                <button
                    @click="navigateToBreadcrumb(null)"
                    @dragover="handleDragOverFolder($event, null)"
                    @dragleave="handleDragLeaveFolder($event, null)"
                    @drop="handleDropOnFolder($event, null, executeMoveItems)"
                    class="flex items-center gap-1 font-medium transition-all rounded-md px-2 py-1 select-none"
                    :class="[
                        folders.length > 0
                            ? 'cursor-pointer text-indigo-600 hover:bg-indigo-50 hover:text-indigo-800'
                            : 'pointer-events-none cursor-default text-slate-800 font-semibold',
                        activeDropTargetId === 'root'
                            ? 'bg-indigo-100 ring-2 ring-indigo-500 text-indigo-900 font-bold scale-105'
                            : ''
                    ]"
                >
                    Meu Drive
                </button>

                <template v-for="(folder, index) in folders" :key="folder.id">
                    <span class="text-slate-400">/</span>
                    <button
                        @click="navigateToBreadcrumb(folder.id)"
                        @dragover="handleDragOverFolder($event, folder.id)"
                        @dragleave="handleDragLeaveFolder($event, folder.id)"
                        @drop="handleDropOnFolder($event, folder.id, executeMoveItems)"
                        class="transition-all rounded-md px-2 py-1 select-none"
                        :class="[
                            index === folders.length - 1
                                ? 'pointer-events-none cursor-default font-semibold text-slate-800'
                                : 'cursor-pointer text-indigo-600 hover:bg-indigo-50 hover:text-indigo-800',
                            activeDropTargetId === folder.id
                                ? 'bg-indigo-100 ring-2 ring-indigo-500 text-indigo-900 font-bold scale-105'
                                : ''
                        ]"
                    >
                        {{ folder.name }}
                    </button>
                </template>
            </div>

            <!-- Botões Nova Pasta / Upload -->
            <div class="flex items-center gap-2">
                <input
                    type="file"
                    ref="fileInput"
                    class="hidden"
                    multiple
                    @change="handleFileUpload"
                />

                <Button
                    v-if="folders.length > 0"
                    @click="triggerFileInput"
                    variant="outline"
                    class="flex cursor-pointer items-center gap-2 rounded-lg border-slate-200 text-slate-700 hover:bg-slate-100 hover:text-slate-900"
                    :disabled="isUploading"
                >
                    <Upload class="h-4 w-4" />
                    Fazer Upload
                </Button>

                <Button
                    @click="openNewFolderModal"
                    class="flex cursor-pointer items-center gap-2 rounded-lg"
                >
                    <Plus class="h-4 w-4" />
                    Nova Pasta
                </Button>
            </div>
        </div>

        <!-- Barra de Busca -->
        <div class="relative max-w-md">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <Input
                v-model="searchQuery"
                type="text"
                placeholder="Buscar arquivos ou pastas..."
                class="pl-9 pr-8 rounded-xl border-slate-200 text-xs"
                @keyup.enter="handleSearch"
            />
            <button
                v-if="searchQuery"
                @click="clearSearch"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
            >
                <X class="h-4 w-4" />
            </button>
        </div>

        <!-- Fila / Card de Status dos Uploads em Andamento -->
        <div v-if="uploadQueue.length > 0" class="space-y-2 rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">
            <h4 class="text-xs font-bold text-indigo-900 flex items-center gap-2">
                <Upload class="h-4 w-4 animate-bounce text-indigo-600" />
                Uploads em andamento ({{ uploadQueue.length }})
            </h4>
            <div class="space-y-2 max-h-36 overflow-y-auto pr-1">
                <div
                    v-for="item in uploadQueue"
                    :key="item.id"
                    class="flex items-center justify-between rounded-lg bg-white p-2.5 text-xs shadow-2xs border border-indigo-50/80"
                >
                    <div class="flex items-center gap-2 truncate max-w-[60%]">
                        <component :is="getFileIcon(item.name)" class="h-4 w-4 shrink-0 text-indigo-500" />
                        <span class="truncate font-medium text-slate-700">{{ item.name }}</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div v-if="item.status === 'uploading'" class="flex items-center gap-2">
                            <div class="h-1.5 w-20 rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    class="h-full bg-indigo-600 transition-all duration-300 rounded-full"
                                    :style="{ width: `${item.progress}%` }"
                                ></div>
                            </div>
                            <span class="text-[10px] font-semibold text-slate-500 w-7 text-right">{{ item.progress }}%</span>
                        </div>

                        <span v-else-if="item.status === 'success'" class="text-emerald-600 flex items-center gap-1 font-semibold">
                            <Check class="h-3.5 w-3.5 stroke-[2.5]" />
                            Pronto
                        </span>
                        <span v-else-if="item.status === 'error'" class="text-rose-600 flex items-center gap-1 font-semibold">
                            <X class="h-3.5 w-3.5 stroke-[2.5]" />
                            Erro
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra Flutuante de Ações em Lote (Seleção Múltipla - Responsiva) -->
        <div
            v-if="selectedDrives.length > 0"
            class="fixed bottom-4 left-4 right-4 z-50 flex items-center justify-between gap-3 rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 text-white shadow-2xl animate-in slide-in-from-bottom-5 duration-200 sm:left-1/2 sm:right-auto sm:-translate-x-1/2 sm:gap-6 sm:px-5"
        >
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                    {{ selectedDrives.length }}
                </span>
                <span class="text-xs sm:text-sm font-medium">
                    <span class="sm:hidden">{{ selectedDrives.length === 1 ? 'item' : 'itens' }}</span>
                    <span class="hidden sm:inline">
                        {{ selectedDrives.length === 1 ? 'item selecionado' : 'itens selecionados' }}
                    </span>
                </span>
            </div>
            <div class="flex items-center gap-1.5 sm:gap-2">
                <Button
                    @click="openBulkMoveModal"
                    variant="outline"
                    class="flex cursor-pointer items-center gap-1.5 text-xs sm:text-sm px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg border-slate-700 bg-slate-800 text-slate-100 hover:bg-slate-700 hover:text-white"
                >
                    <Move class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                    <span class="hidden sm:inline">Mover Selecionados</span>
                    <span class="sm:hidden">Mover</span>
                </Button>
                <Button
                    @click="deleteSelectedDrives"
                    variant="destructive"
                    class="flex cursor-pointer items-center gap-1.5 text-xs sm:text-sm px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg"
                >
                    <Trash2 class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                    <span class="hidden sm:inline">Mover para Lixeira</span>
                    <span class="sm:hidden">Lixeira</span>
                </Button>
                <button
                    @click="clearSelection"
                    class="ml-1 cursor-pointer rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white transition-colors"
                    title="Desmarcar seleção"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Tabela Listagem do Drive -->
        <div
            class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left select-none">
                    <thead>
                        <tr
                            class="border-b border-slate-100 bg-slate-50/70 text-xs font-bold tracking-wider text-slate-600 uppercase"
                        >
                            <th class="w-12 px-4 py-4 text-center">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected"
                                    @change="toggleSelectAll"
                                    :disabled="selectableDrives.length === 0"
                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    :class="selectableDrives.length === 0 ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'"
                                />
                            </th>
                            <th class="px-4 py-4 font-semibold">Nome</th>
                            <th class="px-4 py-4 font-semibold">Criado por</th>
                            <th class="px-4 py-4 font-semibold">Data da criação</th>
                            <th class="px-4 py-4 font-semibold">Data da modificação</th>
                            <th class="px-4 py-4 font-semibold">Tamanho</th>
                            <th class="w-36 px-4 py-4 text-center font-semibold">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        <tr v-if="drives.length === 0">
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <Folder class="mx-auto mb-3 h-12 w-12 stroke-[1.5] text-slate-300" />
                                Nenhum arquivo ou pasta encontrado neste diretório.
                            </td>
                        </tr>

                        <tr
                            v-for="(item, index) in drives"
                            :key="item.id"
                            :draggable="!item.permission_attrs?.disable"
                            @dragstart="handleDragStart($event, item, selectedDrives, drives)"
                            @dragend="handleDragEnd"
                            @dragover="
                                item.document_type === 'folder'
                                    ? handleDragOverFolder($event, item.drive_folder_id, item)
                                    : null
                            "
                            @dragleave="
                                item.document_type === 'folder'
                                    ? handleDragLeaveFolder($event, item.drive_folder_id)
                                    : null
                            "
                            @drop="
                                item.document_type === 'folder'
                                    ? handleDropOnFolder($event, item.drive_folder_id, executeMoveItems)
                                    : null
                            "
                            @click="onRowClick($event, item, index, drives)"
                            @dblclick="navigateToFolder(item)"
                            class="transition-all duration-150"
                            :class="[
                                item.permission_attrs?.disable
                                    ? 'pointer-events-none opacity-60'
                                    : 'cursor-default',
                                isSelected(item.id)
                                    ? 'bg-indigo-50/80 font-medium text-indigo-950'
                                    : 'hover:bg-slate-50/60',
                                activeDropTargetId === item.drive_folder_id
                                    ? 'bg-indigo-100/90 ring-2 ring-indigo-500 scale-[1.002] shadow-sm font-semibold'
                                    : ''
                            ]"
                            :title="item.permission_attrs?.title || ''"
                        >
                            <!-- Checkbox Seleção -->
                            <td class="px-4 py-3 text-center" @click.stop @dblclick.stop>
                                <input
                                    type="checkbox"
                                    v-model="selectedDrives"
                                    :value="item.id"
                                    class="h-4 w-4 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    :disabled="item.permission_attrs?.disable"
                                />
                            </td>

                            <!-- Nome (com Icone) -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <component
                                        :is="getFileIcon(item.document_type)"
                                        class="h-5.5 w-5.5 shrink-0 transition-transform"
                                        :class="getIconColorClass(item.document_type)"
                                    />
                                    <span class="font-medium text-slate-800">
                                        {{ item.name }}
                                    </span>
                                </div>
                            </td>

                            <!-- Criado por -->
                            <td class="px-4 py-3 text-slate-500">
                                {{ item.created_by?.name || "Sistema" }}
                            </td>

                            <!-- Data Criação -->
                            <td class="px-4 py-3 text-slate-500">
                                {{ new Date(item.created_at).toLocaleDateString("pt-BR") }}
                            </td>

                            <!-- Data Modificação -->
                            <td class="px-4 py-3 text-slate-500">
                                {{
                                    item.modification_date ||
                                    new Date(item.updated_at).toLocaleDateString("pt-BR")
                                }}
                                <span v-if="item.modified_by_user" class="text-xs text-slate-400">
                                    - {{ item.modified_by_user.name }}
                                </span>
                            </td>

                            <!-- Tamanho -->
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">
                                {{ item.size_formated || formatSize(item.document_size) }}
                            </td>

                            <!-- Ações -->
                            <td class="px-4 py-3" @click.stop @dblclick.stop>
                                <div class="flex items-center justify-center gap-1">
                                    <!-- Baixar Arquivo -->
                                    <a
                                        v-if="item.document_type !== 'folder'"
                                        :href="route('tenant.drive.download', item.id)"
                                        @click.stop
                                        class="rounded-md p-1.5 text-slate-500 transition-colors hover:bg-slate-100 hover:text-indigo-600"
                                        title="Baixar arquivo"
                                    >
                                        <Upload class="h-4 w-4 rotate-180" />
                                    </a>

                                    <!-- Compartilhar (Verde) -->
                                    <button
                                        @click="openShareModal(item)"
                                        class="cursor-pointer rounded-md p-1.5 text-emerald-600 transition-colors hover:bg-emerald-50 hover:text-emerald-700"
                                        title="Compartilhar acesso"
                                        :disabled="item.permission_attrs?.disable"
                                    >
                                        <Share2 class="h-4.5 w-4.5" />
                                    </button>

                                    <!-- Editar Nome (Azul) -->
                                    <button
                                        @click="openRenameModal(item)"
                                        class="cursor-pointer rounded-md p-1.5 text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-700"
                                        title="Renomear"
                                        :disabled="item.permission_attrs?.disable"
                                    >
                                        <Edit2 class="h-4.5 w-4.5" />
                                    </button>

                                    <!-- Mover (Índigo) -->
                                    <button
                                        @click="openMoveModal(item)"
                                        class="cursor-pointer rounded-md p-1.5 text-indigo-600 transition-colors hover:bg-indigo-50 hover:text-indigo-700"
                                        title="Mover item"
                                        :disabled="item.permission_attrs?.disable"
                                    >
                                        <Move class="h-4.5 w-4.5" />
                                    </button>

                                    <!-- Excluir (Vermelho) -->
                                    <button
                                        @click="confirmDelete(item)"
                                        class="cursor-pointer rounded-md p-1.5 text-rose-600 transition-colors hover:bg-rose-50 hover:text-rose-700"
                                        title="Mover para lixeira"
                                        :disabled="item.permission_attrs?.disable"
                                    >
                                        <Trash2 class="h-4.5 w-4.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Espaço de respiro no final da página (fora do card da tabela) -->
        <div v-if="selectedDrives.length > 0" class="h-28"></div>
    </div>

    <!-- Componentes de Modais Extraídos -->
    <FolderModal
        v-model:isOpen="isNewFolderModalOpen"
        mode="create"
        :currentFolderId="currentFolderId"
        @saved="handleRefreshData"
    />

    <FolderModal
        v-model:isOpen="isRenameModalOpen"
        mode="rename"
        :item="renameItem"
        @saved="handleRefreshData"
    />

    <ShareModal
        v-model:isOpen="isShareModalOpen"
        :item="shareItem"
        @saved="handleRefreshData"
    />

    <DeleteConfirmModal
        v-model:isOpen="isDeleteConfirmOpen"
        :item="itemToDelete"
        :isBulk="isDeletingBulk"
        :selectedCount="selectedDrives.length"
        @confirm="executeDelete"
    />

    <MoveModal
        v-model:isOpen="isMoveModalOpen"
        :itemsToMove="itemsToMove"
        @saved="handleRefreshData"
    />
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
