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
        <div class="border-b border-slate-100 pb-5">
            <h1
                class="flex items-center gap-2 text-3xl font-bold tracking-tight text-slate-800"
            >
                Logs de Exclusão
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Histórico de arquivos e pastas excluídos permanentemente do
                sistema.
            </p>
        </div>

        <!-- Tabela Logs -->
        <div
            class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr
                            class="border-b border-slate-100 bg-slate-50/70 text-xs font-bold tracking-wider text-slate-600 uppercase"
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
                        class="divide-y divide-slate-100 text-sm text-slate-700"
                    >
                        <tr v-if="logs.length === 0">
                            <td
                                colspan="5"
                                class="py-12 text-center text-slate-400"
                            >
                                <ClipboardList
                                    class="mx-auto mb-3 h-12 w-12 stroke-[1.5] text-slate-300"
                                />
                                Nenhum log de exclusão registrado.
                            </td>
                        </tr>

                        <tr
                            v-for="(log, index) in logs"
                            :key="index"
                            class="transition-colors hover:bg-slate-50/50"
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
                                    <span class="font-medium text-slate-800">
                                        {{ log.name || "---" }}
                                    </span>
                                </div>
                            </td>

                            <!-- Caminho do Documento -->
                            <td
                                class="max-w-xs truncate px-6 py-3.5 font-mono text-xs text-slate-500"
                                :title="log.document_path || ''"
                            >
                                {{ log.document_path || "---" }}
                            </td>

                            <!-- Tipo -->
                            <td class="px-6 py-3.5">
                                <span
                                    class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 capitalize"
                                >
                                    {{ log.document_type || "---" }}
                                </span>
                            </td>

                            <!-- Excluído por -->
                            <td class="px-6 py-3.5 font-medium text-slate-600">
                                {{ log.deleted_by || "Sistema" }}
                            </td>

                            <!-- Excluído em (Data/Hora) -->
                            <td class="px-6 py-3.5 text-slate-500">
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
