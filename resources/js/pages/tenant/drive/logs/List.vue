<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import {
    ClipboardList,
} from "lucide-vue-next";
import type { DriveLogData } from "@/types";
import { getFileIcon, getIconColorClass } from "../utils/drive-helpers";

defineOptions({ layout: TenantLayout });

defineProps<{
    logs: DriveLogData[];
}>();
</script>

<template>
    <Head title="Logs de Exclusão do Drive" />

    <div class="space-y-6">
        <!-- Header da Página -->
        <div class="border-b border-border pb-5">
            <h1
                class="flex items-center gap-2 text-3xl font-bold tracking-tight text-foreground"
            >
                Logs de Exclusão
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Histórico de arquivos e pastas excluídos permanentemente do
                sistema.
            </p>
        </div>

        <!-- Tabela Logs -->
        <div
            class="overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr
                            class="border-b border-border bg-muted/60 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            <th class="px-6 py-4 font-semibold">
                                Nome do Item
                            </th>
                            <th class="px-6 py-4 font-semibold">
                                Caminho do Documento
                            </th>
                            <th class="px-6 py-4 font-semibold">Tipo</th>
                            <th class="px-6 py-4 font-semibold">
                                Excluído por
                            </th>
                            <th class="px-6 py-4 font-semibold">Excluído em</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-border text-sm text-foreground"
                    >
                        <tr v-if="logs.length === 0">
                            <td
                                colspan="5"
                                class="py-12 text-center text-muted-foreground"
                            >
                                <ClipboardList
                                    class="mx-auto mb-3 h-12 w-12 stroke-[1.5] text-muted-foreground/50"
                                />
                                Nenhum log de exclusão registrado.
                            </td>
                        </tr>

                        <tr
                            v-for="(log, index) in logs"
                            :key="index"
                            class="transition-colors hover:bg-muted/50"
                        >
                            <!-- Nome (com Icone) -->
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <component
                                        :is="getFileIcon(log.document_type)"
                                        class="h-5.5 w-5.5 shrink-0"
                                        :class="
                                            getIconColorClass(log.document_type)
                                        "
                                    />
                                    <span class="font-medium text-foreground">
                                        {{ log.name || "---" }}
                                    </span>
                                </div>
                            </td>

                            <!-- Caminho do Documento -->
                            <td
                                class="max-w-xs truncate px-6 py-3.5 font-mono text-xs text-muted-foreground"
                                :title="log.document_path || ''"
                            >
                                {{ log.document_path || "---" }}
                            </td>

                            <!-- Tipo -->
                            <td class="px-6 py-3.5">
                                <span
                                    class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-foreground capitalize"
                                >
                                    {{ log.document_type || "---" }}
                                </span>
                            </td>

                            <!-- Excluído por -->
                            <td class="px-6 py-3.5 font-medium text-muted-foreground">
                                {{ log.deleted_by || "Sistema" }}
                            </td>

                            <!-- Excluído em (Data/Hora) -->
                            <td class="px-6 py-3.5 text-muted-foreground">
                                {{
                                    log.deleted_at
                                        ? new Date(
                                              log.deleted_at,
                                          ).toLocaleString("pt-BR")
                                        : "---"
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
