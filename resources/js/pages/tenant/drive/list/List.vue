<script setup lang="ts">
import { ref, computed } from "vue";
import { Head, Link, useForm, router, usePage } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { route } from "ziggy-js";
import { toast } from "vue-sonner";
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
    FileText,
    FileCode,
    FileSpreadsheet,
    FileImage,
    FileArchive,
    File,
    Loader2,
} from "lucide-vue-next";
import type { Drive } from "@/types";
import { getFileIcon, getIconColorClass, formatSize } from "../utils/drive-helpers";

// Subcomponentes extraídos
import ShareModal from "./components/ShareModal.vue";
import FolderModal from "./components/FolderModal.vue";
import DeleteConfirmModal from "./components/DeleteConfirmModal.vue";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    drives: Drive[];
    folders: { id: number; name: string }[];
}>();

const page = usePage();

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

// Seleção múltipla
const selectedDrives = ref<number[]>([]);

// Upload de arquivos
const fileInput = ref<HTMLInputElement | null>(null);
const isUploading = ref(false);
const uploadProgress = ref(0);

// ID da pasta atual baseada na URL
const currentFolderId = computed(() => {
    return page.url.includes("folder_id=")
        ? new URLSearchParams(page.url.split("?")[1]).get("folder_id")
        : null;
});

// Computed para checar se todos os itens estão selecionados
const isAllSelected = computed(() => {
    return (
        props.drives.length > 0 &&
        selectedDrives.value.length === props.drives.length
    );
});

// Toggle selecionar todos
function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedDrives.value = [];
    } else {
        selectedDrives.value = props.drives.map((d) => d.id);
    }
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
    router.visit(route("tenant.drive.index"), {
        data: {
            "my-drive": item.id,
            folder_id: item.drive_folder_id,
            parent_id: item.id,
        },
    });
}

function navigateToBreadcrumb(folderId: number | null) {
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

// Upload de arquivo
function triggerFileInput() {
    fileInput.value?.click();
}

function handleFileUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;

    const file = target.files[0];
    const folderId = currentFolderId.value;

    if (!folderId) {
        toast.error("Selecione ou crie uma pasta para realizar o upload.");
        return;
    }

    const form = useForm({
        documents: [file],
        folder_id: folderId,
        user_id: (page.props as any).auth?.user?.id,
        modified_at: [new Date().toISOString()],
    });

    isUploading.value = true;
    uploadProgress.value = 0;

    form.post(route("tenant.drive.store"), {
        forceFormData: true,
        onProgress: (progress) => {
            if (progress) {
                uploadProgress.value = progress.percentage ?? 0;
            }
        },
        onSuccess: () => {
            isUploading.value = false;
            toast.success("Upload de arquivo realizado com sucesso!");
            if (fileInput.value) fileInput.value.value = "";
        },
        onError: (err) => {
            isUploading.value = false;
            toast.error("Erro ao realizar upload do arquivo.");
            if (fileInput.value) fileInput.value.value = "";
        },
    });
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
    const folderId = currentFolderId.value;

    if (isDeletingBulk.value) {
        router.delete(route("tenant.drive.delete-selected"), {
            data: { selectedValues: selectedDrives.value },
            onSuccess: () => {
                selectedDrives.value = [];
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
                },
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

function handleRefreshData() {
    router.reload();
}
</script>

<template>
    <Head title="Meu Drive" />

    <div class="space-y-6">
        <!-- Header da Página -->
        <div
            class="flex flex-col gap-4 border-b border-slate-100 pb-5 md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1
                    class="flex items-center gap-2 text-3xl font-bold tracking-tight text-slate-800"
                >
                    Meu Drive
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Gerencie, armazene e compartilhe arquivos e pastas com
                    facilidade.
                </p>
            </div>

            <!-- Barra de Busca -->
            <div class="flex w-full max-w-md items-center gap-2 md:w-auto">
                <div class="relative w-full">
                    <Search
                        class="absolute top-2.5 left-3 h-4.5 w-4.5 text-slate-400"
                    />
                    <Input
                        v-model="searchQuery"
                        placeholder="Pesquisar no Drive..."
                        class="w-full rounded-lg border-slate-200 py-2 pr-8 pl-10 focus-visible:ring-indigo-500"
                        @keyup.enter="handleSearch"
                    />
                    <button
                        v-if="searchQuery"
                        class="absolute top-3 right-2.5 text-slate-400 hover:text-slate-600"
                        @click="clearSearch"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <Button
                    @click="handleSearch"
                    class="cursor-pointer rounded-lg px-4"
                >
                    Pesquisar
                </Button>
            </div>
        </div>

        <!-- Trilha de Navegação (Breadcrumbs) e Ações Principais -->
        <div
            class="flex flex-col gap-4 rounded-xl border border-slate-100 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <!-- Breadcrumbs -->
            <div
                class="flex flex-wrap items-center gap-2 text-sm font-medium text-slate-600"
            >
                <button
                    @click="navigateToBreadcrumb(null)"
                    class="flex items-center gap-1 text-indigo-600 transition-colors hover:text-indigo-800"
                    :class="folders.length > 0 ? 'cursor-pointer' : 'pointer-events-none cursor-default text-slate-800 font-semibold'"
                >
                    Meu Drive
                </button>

                <template v-for="(folder, index) in folders" :key="folder.id">
                    <span class="text-slate-400">/</span>
                    <button
                        @click="navigateToBreadcrumb(folder.id)"
                        class="transition-colors hover:text-indigo-800"
                        :class="
                            index === folders.length - 1
                                ? 'pointer-events-none cursor-default font-semibold text-slate-800'
                                : 'cursor-pointer text-indigo-600'
                        "
                    >
                        {{ folder.name }}
                    </button>
                </template>
            </div>

            <!-- Botões Nova Pasta / Upload -->
            <div class="flex items-center gap-2">
                <!-- Só exibe seletor de arquivos se estiver em alguma pasta (currentFolderId existe) -->
                <input
                    type="file"
                    ref="fileInput"
                    class="hidden"
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

        <!-- Barra de Progresso do Upload -->
        <div
            v-if="isUploading"
            class="animate-pulse space-y-2 rounded-xl border border-indigo-100 bg-indigo-50 p-4"
        >
            <div
                class="flex items-center justify-between text-xs font-semibold text-indigo-700"
            >
                <span class="flex items-center gap-2">
                    <Loader2 class="h-4.5 w-4.5 animate-spin" />
                    Realizando upload do arquivo...
                </span>
                <span>{{ Math.round(uploadProgress) }}%</span>
            </div>
            <div class="h-2 w-full rounded-full bg-slate-200">
                <div
                    class="h-2 rounded-full bg-indigo-600 transition-all duration-300"
                    :style="{ width: `${uploadProgress}%` }"
                ></div>
            </div>
        </div>

        <!-- Barra de Ações em Lote (Seleção Múltipla) -->
        <div
            v-if="selectedDrives.length > 0"
            class="animate-fade-in flex items-center justify-between rounded-xl border border-indigo-100 bg-indigo-50 p-4"
        >
            <span class="text-sm font-semibold text-indigo-800">
                {{ selectedDrives.length }}
                {{
                    selectedDrives.length === 1
                        ? "item selecionado"
                        : "itens selecionados"
                }}
            </span>
            <Button
                @click="deleteSelectedDrives"
                variant="destructive"
                class="flex cursor-pointer items-center gap-2 rounded-lg"
            >
                <Trash2 class="h-4 w-4" />
                Mover Selecionados para Lixeira
            </Button>
        </div>

        <!-- Tabela Listagem do Drive -->
        <div
            class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr
                            class="border-b border-slate-100 bg-slate-50/70 text-xs font-bold tracking-wider text-slate-600 uppercase"
                        >
                            <th class="w-12 px-4 py-4 text-center">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected"
                                    @change="toggleSelectAll"
                                    class="h-4 w-4 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                            </th>
                            <th class="px-4 py-4 font-semibold">Nome</th>
                            <th class="px-4 py-4 font-semibold">Criado por</th>
                            <th class="px-4 py-4 font-semibold">
                                Data da criação
                            </th>
                            <th class="px-4 py-4 font-semibold">
                                Data da modificação
                            </th>
                            <th class="px-4 py-4 font-semibold">Tamanho</th>
                            <th
                                class="w-36 px-4 py-4 text-center font-semibold"
                            >
                                Ação
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-100 text-sm text-slate-700"
                    >
                        <tr v-if="drives.length === 0">
                            <td
                                colspan="7"
                                class="py-12 text-center text-slate-400"
                            >
                                <Folder
                                    class="mx-auto mb-3 h-12 w-12 stroke-[1.5] text-slate-300"
                                />
                                Nenhum arquivo ou pasta encontrado neste
                                diretório.
                            </td>
                        </tr>

                        <tr
                            v-for="item in drives"
                            :key="item.id"
                            class="transition-colors hover:bg-slate-50/50"
                            :class="
                                item.permission_attrs.disable
                                    ? 'pointer-events-none opacity-60'
                                    : ''
                            "
                            :title="item.permission_attrs.title || ''"
                        >
                            <!-- Checkbox Seleção -->
                            <td class="px-4 py-3 text-center">
                                <input
                                    type="checkbox"
                                    v-model="selectedDrives"
                                    :value="item.id"
                                    class="h-4 w-4 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    :disabled="item.permission_attrs.disable"
                                />
                            </td>

                            <!-- Nome (com Icone) -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <component
                                        :is="getFileIcon(item.document_type)"
                                        class="h-5.5 w-5.5 shrink-0"
                                        :class="
                                            getIconColorClass(
                                                item.document_type,
                                            )
                                        "
                                    />
                                    <span
                                        v-if="item.document_type === 'folder'"
                                    >
                                        <button
                                            @click="navigateToFolder(item)"
                                            class="cursor-pointer text-left font-medium text-indigo-600 transition-all hover:text-indigo-800 hover:underline"
                                        >
                                            {{ item.name }}
                                        </button>
                                    </span>
                                    <span
                                        v-else
                                        class="font-medium text-slate-800"
                                    >
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
                                {{
                                    new Date(
                                        item.created_at,
                                    ).toLocaleDateString("pt-BR")
                                }}
                            </td>

                            <!-- Data Modificação -->
                            <td class="px-4 py-3 text-slate-500">
                                {{
                                    item.modification_date ||
                                    new Date(
                                        item.updated_at,
                                    ).toLocaleDateString("pt-BR")
                                }}
                                <span
                                    v-if="item.modified_by_user"
                                    class="text-xs text-slate-400"
                                >
                                    - {{ item.modified_by_user.name }}
                                </span>
                            </td>

                            <!-- Tamanho -->
                            <td
                                class="px-4 py-3 font-mono text-xs text-slate-500"
                            >
                                {{
                                    item.size_formated ||
                                    formatSize(item.document_size)
                                }}
                            </td>

                            <!-- Ações -->
                            <td class="px-4 py-3">
                                <div
                                    class="flex items-center justify-center gap-1"
                                >
                                    <!-- Baixar Arquivo -->
                                    <a
                                        v-if="item.document_type !== 'folder'"
                                        :href="
                                            route(
                                                'tenant.drive.download',
                                                item.id,
                                            )
                                        "
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
                                        :disabled="
                                            item.permission_attrs.disable
                                        "
                                    >
                                        <Share2 class="h-4.5 w-4.5" />
                                    </button>

                                    <!-- Editar Nome (Azul) -->
                                    <button
                                        @click="openRenameModal(item)"
                                        class="cursor-pointer rounded-md p-1.5 text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-700"
                                        title="Renomear"
                                        :disabled="
                                            item.permission_attrs.disable
                                        "
                                    >
                                        <Edit2 class="h-4.5 w-4.5" />
                                    </button>

                                    <!-- Excluir (Vermelho) -->
                                    <button
                                        @click="confirmDelete(item)"
                                        class="cursor-pointer rounded-md p-1.5 text-rose-600 transition-colors hover:bg-rose-50 hover:text-rose-700"
                                        title="Mover para lixeira"
                                        :disabled="
                                            item.permission_attrs.disable
                                        "
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
