<script setup lang="ts">
import { ref, computed, watch } from "vue";
import axios from "axios";
import { toast } from "vue-sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import {
    X,
    Loader2,
    Globe,
    Lock,
    Search,
    Share2,
} from "lucide-vue-next";
import type { Drive } from "@/types";
import { getFileIcon, getIconColorClass } from "../../utils/drive-helpers";
import { route } from "ziggy-js";

const props = defineProps<{
    isOpen: boolean;
    item: Drive | null;
}>();

const emit = defineEmits<{
    (e: "update:isOpen", val: boolean): void;
    (e: "saved"): void;
}>();

// Estados Reativos Internos
const allUsers = ref<{ id: number; name: string }[]>([]);
const usersWithAccess = ref<{ id: number; name: string; tipo_permission: string }[]>([]);
const isLoadingUsers = ref(false);
const searchQueryPermission = ref("");
const localIsRestricted = ref(false);
const localAccessUserIds = ref<number[]>([]);
const isSavingPermission = ref(false);

// Monitora abertura do modal para carregar dados
watch(
    () => props.isOpen,
    async (open) => {
        if (open && props.item) {
            await loadShareData(props.item);
        }
    }
);

// Filtra colaboradores com base na busca e remove o proprietário do arquivo
const filteredColleagues = computed(() => {
    if (!props.item) return [];
    const ownerId = Number(props.item.user_id);
    let list = allUsers.value.filter((u) => Number(u.id) !== ownerId);

    if (searchQueryPermission.value.trim()) {
        const query = searchQueryPermission.value.toLowerCase();
        list = list.filter((u) => u.name.toLowerCase().includes(query));
    }
    return list.sort((a, b) => a.name.localeCompare(b.name));
});

async function loadShareData(item: Drive) {
    isLoadingUsers.value = true;
    searchQueryPermission.value = "";
    localAccessUserIds.value = [];
    localIsRestricted.value = false;

    try {
        // 1. Carrega os usuários com acesso atual
        const accessRes = await axios.get(
            route("tenant.drive.permissions.users", item.id),
        );
        if (accessRes.data && accessRes.data.success) {
            usersWithAccess.value = (accessRes.data.data || []).map((u: any) => ({
                id: Number(u.id),
                name: u.name,
                tipo_permission: u.tipo_permission,
            }));
            
            localIsRestricted.value = usersWithAccess.value.some(
                (u) => u.tipo_permission === "somente_proprietario"
            );
            localAccessUserIds.value = usersWithAccess.value
                .filter((u) => u.tipo_permission === "acesso_total")
                .map((u) => Number(u.id));
        }

        // 2. Carrega todos os usuários do sistema
        if (allUsers.value.length === 0) {
            const usersRes = await axios.get(route("tenant.drive.users"));
            if (usersRes.data && usersRes.data.success) {
                allUsers.value = (usersRes.data.data || []).map((u: any) => ({
                    id: Number(u.id),
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

function setLocalRestricted(restricted: boolean) {
    localIsRestricted.value = restricted;
}

function toggleLocalUserAccess(userId: number, shouldHaveAccess: boolean) {
    const id = Number(userId);
    if (shouldHaveAccess) {
        if (!localAccessUserIds.value.includes(id)) {
            localAccessUserIds.value = [...localAccessUserIds.value, id];
        }
    } else {
        localAccessUserIds.value = localAccessUserIds.value.filter(
            (item) => item !== id
        );
    }
}

async function savePermission() {
    if (!props.item) return;

    isSavingPermission.value = true;
    try {
        const initialIsRestricted = usersWithAccess.value.some(
            (u) => u.tipo_permission === "somente_proprietario"
        );
        const initialAccessUserIds = usersWithAccess.value
            .filter((u) => u.tipo_permission === "acesso_total")
            .map((u) => Number(u.id));

        if (!localIsRestricted.value) {
            const ownerPermission = usersWithAccess.value.find(
                (u) => u.tipo_permission === "somente_proprietario"
            );
            if (ownerPermission) {
                await axios.delete(
                    route("tenant.drive.permissions.remove", {
                        drive_id: props.item.id,
                        user_id: ownerPermission.id,
                    }),
                    {
                        validateStatus: (status) => (status >= 200 && status < 300) || status === 409 || status === 302
                    }
                );
            }

            for (const userId of initialAccessUserIds) {
                await axios.delete(
                    route("tenant.drive.permissions.remove", {
                        drive_id: props.item.id,
                        user_id: userId,
                    }),
                    {
                        validateStatus: (status) => (status >= 200 && status < 300) || status === 409 || status === 302
                    }
                );
            }
        } else {
            if (!initialIsRestricted) {
                await axios.post(route("tenant.drive.permissions.store"), {
                    drive_id: props.item.id,
                    permission: "somente_proprietario",
                    users: [],
                });
            }

            const toAdd = localAccessUserIds.value.filter(
                (id) => !initialAccessUserIds.includes(id)
            );
            if (toAdd.length > 0) {
                await axios.post(route("tenant.drive.permissions.store"), {
                    drive_id: props.item.id,
                    permission: "acesso_total",
                    users: toAdd,
                });
            }

            const toRemove = initialAccessUserIds.filter(
                (id) => !localAccessUserIds.value.includes(id)
            );
            for (const userId of toRemove) {
                await axios.delete(
                    route("tenant.drive.permissions.remove", {
                        drive_id: props.item.id,
                        user_id: userId,
                    }),
                    {
                        validateStatus: (status) => (status >= 200 && status < 300) || status === 409 || status === 302
                    }
                );
            }
        }

        toast.success("Configurações de acesso salvas com sucesso!");
        emit("update:isOpen", false);
        emit("saved");
    } catch (e) {
        console.error("Erro ao salvar permissões de acesso:", e);
        toast.error("Erro ao salvar as configurações de acesso.");
    } finally {
        isSavingPermission.value = false;
    }
}
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
    >
        <div
            class="w-full max-w-lg animate-in overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-xl duration-200 zoom-in-95 fade-in"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-border bg-muted/50 px-6 py-4"
            >
                <h3 class="flex items-center gap-2 font-bold text-foreground">
                    <Share2 class="h-5 w-5 text-emerald-500" />
                    Compartilhar Acesso
                </h3>
                <button
                    @click="emit('update:isOpen', false)"
                    class="text-muted-foreground hover:text-foreground cursor-pointer"
                    :disabled="isSavingPermission"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Body -->
            <div class="max-h-[65vh] space-y-5 overflow-y-auto p-6">
                <!-- Info do Item -->
                <div
                    v-if="item"
                    class="flex items-center gap-3 rounded-lg border border-border bg-muted/50 p-3"
                >
                    <component
                        :is="getFileIcon(item.document_type)"
                        class="h-5.5 w-5.5 shrink-0"
                        :class="getIconColorClass(item.document_type)"
                    />
                    <span class="truncate font-semibold text-foreground">{{
                        item.name
                    }}</span>
                </div>

                <!-- Visibilidade / Acesso Geral -->
                <div class="space-y-2">
                    <Label class="text-xs font-bold tracking-wider text-muted-foreground uppercase">
                        Acesso Geral
                    </Label>
                    
                    <div class="flex items-center gap-2">
                        <div class="grid w-full grid-cols-2 gap-2 rounded-lg bg-muted p-1">
                            <button
                                @click="setLocalRestricted(false)"
                                :disabled="isSavingPermission || isLoadingUsers"
                                :class="[
                                    'flex items-center justify-center gap-2 rounded-md py-2 text-xs font-semibold transition-all cursor-pointer',
                                    !localIsRestricted
                                        ? 'bg-card text-foreground shadow-xs'
                                        : 'text-muted-foreground hover:text-foreground'
                                ]"
                            >
                                <Globe class="h-4 w-4" />
                                Público
                            </button>
                            <button
                                @click="setLocalRestricted(true)"
                                :disabled="isSavingPermission || isLoadingUsers"
                                :class="[
                                    'flex items-center justify-center gap-2 rounded-md py-2 text-xs font-semibold transition-all cursor-pointer',
                                    localIsRestricted
                                        ? 'bg-card text-foreground shadow-xs'
                                        : 'text-muted-foreground hover:text-foreground'
                                ]"
                            >
                                <Lock class="h-4 w-4" />
                                Restrito
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Spinner de Carregamento dos Usuários -->
                <div
                    v-if="isLoadingUsers"
                    class="flex items-center justify-center py-10"
                >
                    <Loader2 class="h-8 w-8 animate-spin text-primary" />
                </div>

                <!-- Seção Colaboradores (somente se Restrito) -->
                <div v-else-if="localIsRestricted" class="space-y-4 animate-in fade-in duration-200">
                    <div class="relative">
                        <Search class="absolute top-2.5 left-3 h-4.5 w-4.5 text-muted-foreground" />
                        <Input
                            v-model="searchQueryPermission"
                            type="text"
                            placeholder="Buscar colaboradores..."
                            class="pl-10"
                            :disabled="isSavingPermission"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label class="text-xs font-bold tracking-wider text-muted-foreground uppercase">
                            Colaboradores com acesso
                        </Label>

                        <div
                            v-if="filteredColleagues.length === 0"
                            class="py-4 text-center text-sm text-muted-foreground"
                        >
                            Nenhum colaborador encontrado.
                        </div>

                        <div
                            v-else
                            class="max-h-56 divide-y divide-border overflow-y-auto pr-1 border border-border rounded-lg bg-background"
                        >
                            <div
                                v-for="user in filteredColleagues"
                                :key="user.id"
                                class="flex items-center justify-between px-3 py-2.5 hover:bg-muted/50 transition-colors"
                            >
                                <Label
                                    :for="`user-${user.id}`"
                                    class="text-sm font-medium text-foreground cursor-pointer flex-1 select-none pr-4"
                                >
                                    {{ user.name }}
                                </Label>

                                <div class="flex items-center min-w-[24px] justify-center">
                                    <Checkbox
                                        :id="`user-${user.id}`"
                                        :checked="localAccessUserIds.includes(user.id)"
                                        :modelValue="localAccessUserIds.includes(user.id)"
                                        :disabled="isSavingPermission"
                                        @update:checked="(val: any) => toggleLocalUserAccess(user.id, val)"
                                        @update:modelValue="(val: any) => toggleLocalUserAccess(user.id, val)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Painel Informativo quando Público -->
                <div
                    v-else
                    class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-4 text-foreground animate-in fade-in duration-200"
                >
                    <div class="flex gap-3">
                        <Globe class="h-5 w-5 shrink-0 text-emerald-500" />
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-foreground">Qualquer pessoa da organização</p>
                            <p class="text-xs text-muted-foreground leading-relaxed">
                                Este item está definido como público. Todos os colaboradores podem visualizá-lo e acessá-lo livremente a partir da listagem geral do drive.
                            </p>
                        </div>
                    </div>
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
                    :disabled="isSavingPermission"
                >
                    Cancelar
                </Button>
                <Button
                    @click="savePermission"
                    class="cursor-pointer font-semibold"
                    :disabled="isSavingPermission || isLoadingUsers"
                >
                    <Loader2
                        v-if="isSavingPermission"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    Salvar
                </Button>
            </div>
        </div>
    </div>
</template>
