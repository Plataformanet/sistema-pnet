<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { Head, Link, useForm, router, usePage } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { route } from "ziggy-js";
import axios from "axios";
import { toast } from "vue-sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import {
    Folder,
    FileText,
    FileCode,
    FileSpreadsheet,
    FileImage,
    FileArchive,
    File,
    Share2,
    Edit2,
    Trash2,
    Search,
    Plus,
    Upload,
    ArrowLeft,
    X,
    Check,
    Loader2,
} from "lucide-vue-next";
import type { Drive, DriveFolder, DrivePermission, User } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    drives: Drive[];
    folders: { id: number; name: string }[];
}>();

const page = usePage();

// Estados Reativos
const searchQuery = ref("");
const isNewFolderModalOpen = ref(false);
const newFolderName = ref("");
const isRenameModalOpen = ref(false);
const renameItem = ref<Drive | null>(null);
const renameName = ref("");
const isShareModalOpen = ref(false);
const shareItem = ref<Drive | null>(null);
const isDeleteConfirmOpen = ref(false);
const itemToDelete = ref<Drive | null>(null);
const isDeletingBulk = ref(false);

// Estados de permissão/compartilhamento
const selectedPermissionType = ref<
    "somente_proprietario" | "somente_leitura" | "acesso_total"
>("somente_leitura");
const allUsers = ref<{ id: number; name: string }[]>([]);
const usersWithAccess = ref<
    { id: number; name: string; tipo_permission: string }[]
>([]);
const selectedUsersToShare = ref<number[]>([]);
const isLoadingUsers = ref(false);
const isSavingPermission = ref(false);

// Seleção múltipla
const selectedDrives = ref<number[]>([]);

// Upload de arquivos
const fileInput = ref<HTMLInputElement | null>(null);
const isUploading = ref(false);
const uploadProgress = ref(0);

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

// Icones por tipo
function getFileIcon(type: string) {
    switch (type) {
        case "folder":
            return Folder;
        case "pdf":
            return FileText;
        case "docx":
            return FileText;
        case "xlsx":
            return FileSpreadsheet;
        case "txt":
            return FileText;
        case "jpg":
        case "png":
            return FileImage;
        case "zip":
        case "tar":
            return FileArchive;
        default:
            return File;
    }
}

// Cores por tipo de icone
function getIconColorClass(type: string) {
    switch (type) {
        case "folder":
            return "text-amber-500 fill-amber-500";
        case "pdf":
            return "text-rose-500 fill-rose-50";
        case "docx":
            return "text-blue-500 fill-blue-50";
        case "xlsx":
            return "text-emerald-600 fill-emerald-50";
        case "txt":
            return "text-slate-500";
        case "jpg":
        case "png":
            return "text-violet-500 fill-violet-50";
        case "zip":
        case "tar":
            return "text-orange-500 fill-orange-50";
        default:
            return "text-slate-400";
    }
}

// Formatar Bytes para exibição amigável no frontend (caso o assessor size_formated falhe)
function formatSize(bytes: number): string {
    if (bytes === 0) return "---";
    const units = ["Bytes", "KB", "MB", "GB", "TB"];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return parseFloat((bytes / Math.pow(1024, i)).toFixed(1)) + " " + units[i];
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
        // Encontra o drive correspondente ao folderId
        router.visit(route("tenant.drive.index"), {
            data: { folder_id: folderId },
        });
    }
}

// Criar nova pasta
function openNewFolderModal() {
    newFolderName.value = "";
    isNewFolderModalOpen.value = true;
}

function createFolder() {
    if (!newFolderName.value.trim()) {
        toast.error("O nome da pasta não pode ser vazio.");
        return;
    }

    const currentFolderId = page.url.includes("folder_id=")
        ? new URLSearchParams(page.url.split("?")[1]).get("folder_id")
        : null;

    router.post(
        route("tenant.drive.folders.store"),
        {
            name: newFolderName.value,
            parent_id: currentFolderId,
        },
        {
            onSuccess: () => {
                isNewFolderModalOpen.value = false;
                toast.success("Pasta criada com sucesso!");
            },
            onError: (errors) => {
                toast.error(errors.name || "Erro ao criar pasta.");
            },
        },
    );
}

// Upload de arquivo
function triggerFileInput() {
    fileInput.value?.click();
}

function handleFileUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;

    const file = target.files[0];
    const currentFolderId = page.url.includes("folder_id=")
        ? new URLSearchParams(page.url.split("?")[1]).get("folder_id")
        : null;

    if (!currentFolderId) {
        toast.error("Selecione ou crie uma pasta para realizar o upload.");
        return;
    }

    const form = useForm({
        documents: [file],
        folder_id: currentFolderId,
        user_id: page.props.auth.user.id,
        modified_at: [new Date().toISOString()],
    });

    isUploading.value = true;
    uploadProgress.value = 0;

    form.post(route("tenant.drive.store"), {
        forceFormData: true,
        onProgress: (progress) => {
            if (progress) {
                uploadProgress.value = progress.percentage;
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

    if (isDeletingBulk.value) {
        router.delete(route("tenant.drive.delete-selected"), {
            data: { selectedValues: selectedDrives.value },
            onSuccess: () => {
                selectedDrives.value = [];
                toast.success("Itens movidos para a lixeira com sucesso!");
            },
            onError: () =>
                toast.error("Erro ao excluir os itens selecionados."),
        });
    } else if (itemToDelete.value) {
        const item = itemToDelete.value;
        if (item.document_type === "folder") {
            router.delete(
                route("tenant.drive.folders.destroy", item.drive_folder_id),
                {
                    onSuccess: () =>
                        toast.success("Pasta movida para a lixeira!"),
                    onError: () => toast.error("Erro ao excluir a pasta."),
                },
            );
        } else {
            router.delete(route("tenant.drive.destroy", item.id), {
                onSuccess: () =>
                    toast.success("Arquivo movido para a lixeira!"),
                onError: () => toast.error("Erro ao excluir o arquivo."),
            });
        }
    }
}

// Renomear item
function openRenameModal(item: Drive) {
    renameItem.value = item;
    // Remove a extensão do nome no input se for arquivo para facilitar
    if (item.document_type !== "folder" && item.name.includes(".")) {
        const parts = item.name.split(".");
        parts.pop();
        renameName.value = parts.join(".");
    } else {
        renameName.value = item.name;
    }
    isRenameModalOpen.value = true;
}

function saveRename() {
    if (!renameItem.value || !renameName.value.trim()) return;

    router.put(
        route("tenant.drive.update"),
        {
            id: renameItem.value.id,
            name: renameName.value,
            type_drive: renameItem.value.document_type === "folder" ? 1 : 2,
            drive_type:
                renameItem.value.document_type === "folder"
                    ? "folder"
                    : renameItem.value.document_type,
        },
        {
            onSuccess: () => {
                isRenameModalOpen.value = false;
                toast.success("Item renomeado com sucesso!");
            },
            onError: (err) => {
                toast.error("Erro ao renomear o item.");
            },
        },
    );
}

// Compartilhar / Permissões
async function openShareModal(item: Drive) {
    shareItem.value = item;
    isShareModalOpen.value = true;
    isLoadingUsers.value = true;
    selectedUsersToShare.value = [];
    selectedPermissionType.value = "somente_leitura";

    try {
        // 1. Carrega os usuários com acesso atual
        const accessRes = await axios.get(
            route("tenant.drive.permissions.users", item.id),
        );
        if (accessRes.data && accessRes.data.success) {
            usersWithAccess.value = accessRes.data.data || [];
        }

        // 2. Carrega todos os usuários do sistema (endpoint JSON dedicado)
        if (allUsers.value.length === 0) {
            const usersRes = await axios.get(route("tenant.drive.users"));

            if (usersRes.data && usersRes.data.success) {
                allUsers.value = (usersRes.data.data || []).map((u: any) => ({
                    id: u.id,
                    name: u.name,
                }));
            }
        }
    } catch (e) {
        console.error("Erro ao carregar dados do compartilhamento:", e);
        toast.error("Erro ao carregar a lista de usuários.");
    } finally {
        isLoadingUsers.value = false;
    }
}

// Adicionar permissão
async function savePermission() {
    if (!shareItem.value || selectedUsersToShare.value.length === 0) {
        toast.error("Selecione pelo menos um usuário.");
        return;
    }

    isSavingPermission.value = true;
    try {
        const res = await axios.post(route("tenant.drive.permissions.store"), {
            drive_id: shareItem.value.id,
            users: selectedUsersToShare.value,
            permission: selectedPermissionType.value,
        });

        if (res.data && res.data.success) {
            toast.success("Permissões de acesso compartilhadas!");

            // Recarrega as permissões do item
            const accessRes = await axios.get(
                route("tenant.drive.permissions.users", shareItem.value.id),
            );
            if (accessRes.data && accessRes.data.success) {
                usersWithAccess.value = accessRes.data.data || [];
            }
            selectedUsersToShare.value = [];
        } else {
            toast.error("Erro ao salvar as permissões.");
        }
    } catch (e) {
        console.error("Erro ao salvar permissões:", e);
        toast.error("Erro ao compartilhar acesso.");
    } finally {
        isSavingPermission.value = false;
    }
}

// Remover permissão de um usuário
async function removePermission(userId: number) {
    if (!shareItem.value) return;

    if (confirm("Deseja remover o acesso deste usuário?")) {
        try {
            await axios.delete(
                route("tenant.drive.permissions.remove", {
                    drive_id: shareItem.value.id,
                    user_id: userId,
                }),
            );

            toast.success("Acesso removido com sucesso!");

            // Filtra o usuário removido da lista local
            usersWithAccess.value = usersWithAccess.value.filter(
                (u) => u.id !== userId,
            );
        } catch (e) {
            console.error("Erro ao remover permissão:", e);
            toast.error("Erro ao remover a permissão.");
        }
    }
}

// Filtrar usuários que ainda não possuem permissão
const availableUsersToShare = computed(() => {
    const activeUserIds = usersWithAccess.value.map((u) => u.id);
    // Exclui também o proprietário do item
    if (shareItem.value) {
        activeUserIds.push(shareItem.value.user_id);
    }
    return allUsers.value.filter((u) => !activeUserIds.includes(u.id));
});
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

    <!-- MODAL: NOVA PASTA -->
    <div
        v-if="isNewFolderModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs"
    >
        <div
            class="w-full max-w-md animate-in overflow-hidden rounded-xl border border-slate-100 bg-white shadow-xl duration-200 zoom-in-95 fade-in"
        >
            <div
                class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-6 py-4"
            >
                <h3 class="flex items-center gap-2 font-bold text-slate-800">
                    <Folder class="h-5 w-5 fill-amber-500 text-amber-500" />
                    Criar Nova Pasta
                </h3>
                <button
                    @click="isNewFolderModalOpen = false"
                    class="text-slate-400 hover:text-slate-600"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>
            <div class="space-y-4 p-6">
                <div class="space-y-1">
                    <label
                        class="text-xs font-semibold tracking-wider text-slate-500 uppercase"
                        >Nome da Pasta</label
                    >
                    <Input
                        v-model="newFolderName"
                        placeholder="Digite o nome da pasta..."
                        class="focus-visible:ring-indigo-500"
                        @keyup.enter="createFolder"
                    />
                </div>
            </div>
            <div
                class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4"
            >
                <Button
                    @click="isNewFolderModalOpen = false"
                    variant="ghost"
                    class="text-slate-600"
                    >Cancelar</Button
                >
                <Button
                    @click="createFolder"
                    class="cursor-pointer"
                    >Criar Pasta</Button
                >
            </div>
        </div>
    </div>

    <!-- MODAL: RENOMEAR -->
    <div
        v-if="isRenameModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs"
    >
        <div
            class="w-full max-w-md animate-in overflow-hidden rounded-xl border border-slate-100 bg-white shadow-xl duration-200 zoom-in-95 fade-in"
        >
            <div
                class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-6 py-4"
            >
                <h3 class="flex items-center gap-2 font-bold text-slate-800">
                    <Edit2 class="h-4.5 w-4.5 text-blue-600" />
                    Renomear Item
                </h3>
                <button
                    @click="isRenameModalOpen = false"
                    class="text-slate-400 hover:text-slate-600"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>
            <div class="space-y-4 p-6">
                <div class="space-y-1">
                    <label
                        class="text-xs font-semibold tracking-wider text-slate-500 uppercase"
                        >Novo Nome</label
                    >
                    <Input
                        v-model="renameName"
                        placeholder="Digite o novo nome..."
                        class="focus-visible:ring-indigo-500"
                        @keyup.enter="saveRename"
                    />
                </div>
            </div>
            <div
                class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4"
            >
                <Button
                    @click="isRenameModalOpen = false"
                    variant="ghost"
                    class="text-slate-600"
                    >Cancelar</Button
                >
                <Button
                    @click="saveRename"
                    class="cursor-pointer"
                    >Salvar</Button
                >
            </div>
        </div>
    </div>

    <!-- MODAL: COMPARTILHAR / PERMISSÕES -->
    <div
        v-if="isShareModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs"
    >
        <div
            class="w-full max-w-lg animate-in overflow-hidden rounded-xl border border-slate-100 bg-white shadow-xl duration-200 zoom-in-95 fade-in"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-6 py-4"
            >
                <h3 class="flex items-center gap-2 font-bold text-slate-800">
                    <Share2 class="h-5 w-5 text-emerald-600" />
                    Compartilhar Acesso
                </h3>
                <button
                    @click="isShareModalOpen = false"
                    class="text-slate-400 hover:text-slate-600"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Body -->
            <div class="max-h-[60vh] space-y-6 overflow-y-auto p-6">
                <div
                    v-if="shareItem"
                    class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3"
                >
                    <component
                        :is="getFileIcon(shareItem.document_type)"
                        class="h-5.5 w-5.5 shrink-0"
                        :class="getIconColorClass(shareItem.document_type)"
                    />
                    <span class="truncate font-semibold text-slate-700">{{
                        shareItem.name
                    }}</span>
                </div>

                <!-- Formulário para Adicionar Acesso -->
                <div class="space-y-3">
                    <h4
                        class="text-xs font-bold tracking-wider text-slate-400 uppercase"
                    >
                        Conceder Acesso a Usuários
                    </h4>

                    <div
                        v-if="isLoadingUsers"
                        class="flex items-center justify-center py-4"
                    >
                        <Loader2 class="h-6 w-6 animate-spin text-indigo-600" />
                    </div>

                    <div
                        v-else-if="availableUsersToShare.length === 0"
                        class="py-2 text-sm text-slate-400"
                    >
                        Todos os usuários do sistema já possuem permissões de
                        acesso configuradas para este item.
                    </div>

                    <div v-else class="space-y-3">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Seleção do Usuário -->
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-semibold text-slate-500"
                                    >Usuários</label
                                >
                                <select
                                    v-model="selectedUsersToShare"
                                    multiple
                                    class="h-24 w-full rounded-lg border border-slate-200 bg-white p-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option
                                        v-for="user in availableUsersToShare"
                                        :key="user.id"
                                        :value="user.id"
                                    >
                                        {{ user.name }}
                                    </option>
                                </select>
                                <span
                                    class="mt-1 block text-[10px] text-slate-400"
                                    >Dica: Segure Ctrl para selecionar
                                    múltiplos.</span
                                >
                            </div>

                            <!-- Seleção da Permissão -->
                            <div class="space-y-2">
                                <label
                                    class="text-xs font-semibold text-slate-500"
                                    >Nível de Permissão</label
                                >
                                <div class="space-y-2">
                                    <label
                                        class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-600"
                                    >
                                        <input
                                            type="radio"
                                            v-model="selectedPermissionType"
                                            value="somente_leitura"
                                            class="text-indigo-600 focus:ring-indigo-500"
                                        />
                                        Somente Leitura
                                    </label>
                                    <label
                                        class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-600"
                                    >
                                        <input
                                            type="radio"
                                            v-model="selectedPermissionType"
                                            value="acesso_total"
                                            class="text-indigo-600 focus:ring-indigo-500"
                                        />
                                        Acesso Total (Leitura e Escrita)
                                    </label>
                                    <label
                                        class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-600"
                                    >
                                        <input
                                            type="radio"
                                            v-model="selectedPermissionType"
                                            value="somente_proprietario"
                                            class="text-indigo-600 focus:ring-indigo-500"
                                        />
                                        Bloquear (Somente Proprietário)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <Button
                                @click="savePermission"
                                class="flex cursor-pointer items-center gap-2 rounded-lg"
                                :disabled="isSavingPermission"
                            >
                                <Loader2
                                    v-if="isSavingPermission"
                                    class="h-4 w-4 animate-spin"
                                />
                                Compartilhar
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Lista de Usuários que já têm Acesso -->
                <div class="space-y-3">
                    <h4
                        class="text-xs font-bold tracking-wider text-slate-400 uppercase"
                    >
                        Usuários com Acesso Atual
                    </h4>

                    <div
                        v-if="usersWithAccess.length === 0"
                        class="py-2 text-sm text-slate-400"
                    >
                        Nenhum acesso compartilhado configurado para outros
                        usuários (somente o proprietário).
                    </div>

                    <div
                        v-else
                        class="max-h-48 divide-y divide-slate-100 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="user in usersWithAccess"
                            :key="user.id"
                            class="flex items-center justify-between py-2 text-sm"
                        >
                            <span class="font-medium text-slate-700">{{
                                user.name
                            }}</span>
                            <div class="flex items-center gap-2">
                                <span
                                    class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 capitalize"
                                >
                                    {{ user.tipo_permission.replace("_", " ") }}
                                </span>
                                <button
                                    @click="removePermission(user.id)"
                                    class="cursor-pointer rounded-md p-1 text-rose-600 transition-colors hover:bg-rose-50 hover:text-rose-800"
                                    title="Remover Acesso"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex items-center justify-end border-t border-slate-100 bg-slate-50 px-6 py-4"
            >
                <Button
                    @click="isShareModalOpen = false"
                    class="cursor-pointer rounded-lg bg-slate-800 text-white hover:bg-slate-700"
                    >Fechar</Button
                >
            </div>
        </div>
    </div>

    <!-- DIÁLOGO DE CONFIRMAÇÃO DE EXCLUSÃO -->
    <AlertDialog :open="isDeleteConfirmOpen" @update:open="isDeleteConfirmOpen = $event">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Confirmar Exclusão</AlertDialogTitle>
                <AlertDialogDescription>
                    <span v-if="isDeletingBulk">
                        Tem certeza que deseja mover os {{ selectedDrives.length }} itens selecionados para a lixeira?
                    </span>
                    <span v-else-if="itemToDelete">
                        Tem certeza que deseja mover "{{ itemToDelete.name }}" para a lixeira?
                    </span>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="isDeleteConfirmOpen = false">Cancelar</AlertDialogCancel>
                <AlertDialogAction @click="executeDelete" class="bg-rose-600 text-white hover:bg-rose-700">
                    Mover para a lixeira
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
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
