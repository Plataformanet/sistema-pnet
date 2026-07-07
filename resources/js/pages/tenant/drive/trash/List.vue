<script setup lang="ts">
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { route } from "ziggy-js";
import axios from "axios";
import { toast } from "vue-sonner";
import { Button } from "@/components/ui/button";
import {
    Folder,
    FileText,
    FileCode,
    FileSpreadsheet,
    FileImage,
    FileArchive,
    File,
    Undo,
    Trash2,
    X,
    FolderSync,
} from "lucide-vue-next";
import type { Drive } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    drives: Drive[];
    folders: { id: number; name: string }[];
}>();

const isClearingTrash = ref(false);
const isProcessingAction = ref<number | null>(null);

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

// Formatar Bytes para exibição amigável
function formatSize(bytes: number): string {
    if (bytes === 0) return "---";
    const units = ["Bytes", "KB", "MB", "GB", "TB"];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return parseFloat((bytes / Math.pow(1024, i)).toFixed(1)) + " " + units[i];
}

// Navegar na lixeira se for pasta deletada
function navigateToTrashFolder(item: Drive) {
    if (item.document_type !== "folder") return;
    router.visit(route("tenant.drive.trash.index"), {
        data: { folder_id: item.drive_folder_id },
    });
}

function navigateToBreadcrumb(folderId: number | null) {
    router.visit(route("tenant.drive.trash.index"), {
        data: folderId ? { folder_id: folderId } : {},
    });
}

// Restaurar item
async function restoreItem(item: Drive) {
    isProcessingAction.value = item.id;
    try {
        const res = await axios.post(route("tenant.drive.trash.restore"), {
            id: item.id,
            tipo_drive: item.document_type,
        });

        if (res.data && res.data.success) {
            toast.success(res.data.message || "Item restaurado com sucesso!");
            router.reload({ only: ["drives"] });
        } else {
            toast.error(res.data.message || "Erro ao restaurar o item.");
        }
    } catch (e) {
        console.error("Erro ao restaurar item:", e);
        toast.error("Erro ao restaurar o item.");
    } finally {
        isProcessingAction.value = null;
    }
}

// Excluir permanentemente
async function forceDeleteItem(item: Drive) {
    if (
        !confirm(
            `Tem certeza que deseja excluir permanentemente "${item.name}"? Esta ação não pode ser desfeita.`,
        )
    ) {
        return;
    }

    isProcessingAction.value = item.id;
    try {
        const res = await axios.delete(
            route("tenant.drive.trash.force-delete"),
            {
                data: {
                    id: item.id,
                    drive_type: item.document_type,
                    confirm_delete: 1,
                },
            },
        );

        if (res.data && res.data.success) {
            toast.success(res.data.message || "Item excluído permanentemente!");
            router.reload({ only: ["drives"] });
        } else {
            toast.error(res.data.message || "Erro ao excluir o item.");
        }
    } catch (e) {
        console.error("Erro ao excluir permanentemente:", e);
        toast.error("Erro ao excluir o item permanentemente.");
    } finally {
        isProcessingAction.value = null;
    }
}

// Esvaziar Lixeira
async function clearTrash() {
    if (props.drives.length === 0) return;

    if (
        !confirm(
            "Tem certeza que deseja esvaziar a lixeira? Todos os itens listados serão excluídos permanentemente!",
        )
    ) {
        return;
    }

    isClearingTrash.value = true;

    // Mapeia todos os itens da lixeira
    const selectedDrives = props.drives.map((d) => ({
        id: d.id,
        drive_type: d.document_type,
    }));

    try {
        const res = await axios.post(route("tenant.drive.trash.clear"), {
            selected_drives: selectedDrives,
            confirm_delete: 1,
        });

        if (res.data && res.data.success) {
            toast.success(res.data.message || "Lixeira esvaziada com sucesso!");
            router.reload({ only: ["drives"] });
        } else {
            toast.error(res.data.message || "Erro ao esvaziar a lixeira.");
        }
    } catch (e) {
        console.error("Erro ao esvaziar a lixeira:", e);
        toast.error("Erro ao esvaziar a lixeira.");
    } finally {
        isClearingTrash.value = false;
    }
}
</script>

<template>
    <Head title="Lixeira do Drive" />

    <div class="space-y-6">
        <!-- Header da Página -->
        <div
            class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="flex items-center gap-2 text-3xl font-bold tracking-tight text-slate-800"
                >
                    Lixeira
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Itens excluídos permanecem aqui e podem ser restaurados ou
                    deletados permanentemente.
                </p>
            </div>

            <!-- Botão Esvaziar Lixeira -->
            <Button
                v-if="drives.length > 0"
                @click="clearTrash"
                variant="destructive"
                class="flex w-full cursor-pointer items-center gap-2 rounded-lg sm:w-auto"
                :disabled="isClearingTrash"
            >
                <Trash2 class="h-4.5 w-4.5" />
                {{ isClearingTrash ? "Esvaziando..." : "Esvaziar Lixeira" }}
            </Button>
        </div>

        <!-- Trilha de Navegação (Breadcrumbs) na Lixeira -->
        <div
            v-if="folders.length > 0"
            class="flex items-center gap-2 rounded-xl border border-slate-100 bg-slate-50 p-4 text-sm font-medium text-slate-600"
        >
            <button
                @click="navigateToBreadcrumb(null)"
                class="flex items-center gap-1 text-indigo-600 transition-colors hover:text-indigo-800"
            >
                Lixeira principal
            </button>

            <template v-for="(folder, index) in folders" :key="folder.id">
                <span class="text-slate-400">/</span>
                <button
                    @click="navigateToBreadcrumb(folder.id)"
                    class="transition-colors hover:text-indigo-800"
                    :class="
                        index === folders.length - 1
                            ? 'pointer-events-none cursor-default font-semibold text-slate-800'
                            : 'text-indigo-600'
                    "
                >
                    {{ folder.name }}
                </button>
            </template>
        </div>

        <!-- Tabela Lixeira -->
        <div
            class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr
                            class="border-b border-slate-100 bg-slate-50/70 text-xs font-bold tracking-wider text-slate-600 uppercase"
                        >
                            <th class="px-6 py-4 font-semibold">Nome</th>
                            <th class="px-6 py-4 font-semibold">Criado por</th>
                            <th class="px-6 py-4 font-semibold">
                                Data da criação
                            </th>
                            <th class="px-6 py-4 font-semibold">
                                Data da exclusão
                            </th>
                            <th class="px-6 py-4 font-semibold">Tamanho</th>
                            <th
                                class="w-36 px-6 py-4 text-center font-semibold"
                            >
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-100 text-sm text-slate-700"
                    >
                        <tr v-if="drives.length === 0">
                            <td
                                colspan="6"
                                class="py-12 text-center text-slate-400"
                            >
                                <Trash2
                                    class="mx-auto mb-3 h-12 w-12 stroke-[1.5] text-slate-300"
                                />
                                A lixeira está vazia.
                            </td>
                        </tr>

                        <tr
                            v-for="item in drives"
                            :key="item.id"
                            class="transition-colors hover:bg-slate-50/50"
                            :class="
                                isProcessingAction === item.id
                                    ? 'pointer-events-none opacity-50'
                                    : ''
                            "
                        >
                            <!-- Nome (com Icone) -->
                            <td class="px-6 py-3.5">
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
                                            @click="navigateToTrashFolder(item)"
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
                            <td class="px-6 py-3.5 text-slate-500">
                                {{ item.created_by?.name || "Sistema" }}
                            </td>

                            <!-- Data Criação -->
                            <td class="px-6 py-3.5 text-slate-500">
                                {{
                                    new Date(
                                        item.created_at,
                                    ).toLocaleDateString("pt-BR")
                                }}
                            </td>

                            <!-- Data Exclusão (deleted_at) -->
                            <td class="px-6 py-3.5 text-slate-500">
                                {{
                                    item.deleted_at
                                        ? new Date(
                                              item.deleted_at,
                                          ).toLocaleDateString("pt-BR")
                                        : "---"
                                }}
                            </td>

                            <!-- Tamanho -->
                            <td
                                class="px-6 py-3.5 font-mono text-xs text-slate-500"
                            >
                                {{
                                    item.size_formated ||
                                    formatSize(item.document_size)
                                }}
                            </td>

                            <!-- Ações -->
                            <td class="px-6 py-3.5">
                                <div
                                    class="flex items-center justify-center gap-2"
                                >
                                    <!-- Restaurar -->
                                    <button
                                        @click="restoreItem(item)"
                                        class="cursor-pointer rounded-md p-1.5 text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-700"
                                        title="Restaurar item"
                                    >
                                        <Undo class="h-4.5 w-4.5" />
                                    </button>

                                    <!-- Excluir Permanentemente -->
                                    <button
                                        @click="forceDeleteItem(item)"
                                        class="cursor-pointer rounded-md p-1.5 text-rose-600 transition-colors hover:bg-rose-50 hover:text-rose-700"
                                        title="Excluir permanentemente"
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
</template>
