<script setup lang="ts">
import { ServerDataTable } from "@/components/ui/data-table";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Head } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import axios, { AxiosError } from "axios";
import { toast } from "vue-sonner";
import { computed, ref, watch } from "vue";
import { buildColumns } from "./columns";
import ReportFilters from "../components/ReportFilters.vue";
import ZipDownloadBar from "../components/ZipDownloadBar.vue";
import type {
    FinishedProposalRow,
    FinishedProposalsFilters,
    Paginated,
    ReportDevelopmentOption,
    ZipLimits,
} from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    proposals: Paginated<FinishedProposalRow>;
    downloadableCount: number;
    developments: ReportDevelopmentOption[];
    zipLimits: ZipLimits;
    filters: FinishedProposalsFilters;
}>();

const listUrl = route("tenant.documents.reports.finished-proposals.list");

// Seleção local: ids marcados nesta página ou "todos os resultados do filtro".
const selected = ref<number[]>([]);
const allMatching = ref(false);
const downloading = ref(false);

const selectableOnPage = computed(() => props.proposals.data.filter((row) => row.document).map((row) => row.id));
const selectedCount = computed(() => (allMatching.value ? props.downloadableCount : selected.value.length));
const pageFullySelected = computed(
    () => selectableOnPage.value.length > 0 && selectableOnPage.value.every((id) => selected.value.includes(id)),
);

function clearSelection() {
    selected.value = [];
    allMatching.value = false;
}

// Trocar de página limpa a seleção da página; trocar o filtro limpa tudo.
watch(
    () => props.proposals.current_page,
    () => {
        if (!allMatching.value) {
            selected.value = [];
        }
    },
);
watch(() => JSON.stringify(props.filters), clearSelection);

const columns = computed(() =>
    buildColumns({
        isSelected: (id) => allMatching.value || selected.value.includes(id),
        toggle: (id, checked) => {
            if (allMatching.value) {
                allMatching.value = false;
                selected.value = [...selectableOnPage.value];
            }

            selected.value = checked ? [...new Set([...selected.value, id])] : selected.value.filter((item) => item !== id);
        },
        allOnPage: () =>
            allMatching.value || pageFullySelected.value ? true : selected.value.length > 0 ? "indeterminate" : false,
        toggleAllOnPage: (checked) => {
            allMatching.value = false;
            selected.value = checked ? [...selectableOnPage.value] : [];
        },
    }),
);

const extraParams = computed(() => ({
    start_date: props.filters.start_date ?? undefined,
    end_date: props.filters.end_date ?? undefined,
    development_id: props.filters.development_id ?? undefined,
}));

async function readJson(data: unknown): Promise<Record<string, any>> {
    try {
        return JSON.parse(data instanceof Blob ? await data.text() : String(data ?? "{}"));
    } catch {
        return {};
    }
}

/**
 * O ZIP volta como arquivo (até o limite síncrono) ou como 202 com aviso de
 * que será enviado por e-mail, por isso o envio é por axios e não pelo
 * router do Inertia.
 */
async function download() {
    downloading.value = true;

    try {
        const payload = allMatching.value ? { ...extraParams.value, all_matching: true } : { proposal_ids: selected.value };
        const response = await axios.post(route("tenant.documents.reports.finished-proposals.download"), payload, {
            responseType: "blob",
        });

        if (response.status === 202) {
            toast.success((await readJson(response.data)).message ?? "O ZIP será enviado por e-mail.");
            clearSelection();

            return;
        }

        const disposition = String(response.headers["content-disposition"] ?? "");
        const fileName = /filename="?([^";]+)"?/.exec(disposition)?.[1] ?? "relatorio-propostas-finalizadas.zip";
        const url = URL.createObjectURL(response.data as Blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = fileName;
        link.click();
        URL.revokeObjectURL(url);
    } catch (error) {
        const body = await readJson((error as AxiosError).response?.data);
        const firstError = body.errors ? (Object.values(body.errors)[0] as string[] | undefined)?.[0] : undefined;

        toast.error(firstError ?? body.message ?? "Erro ao gerar o ZIP do relatório.");
    } finally {
        downloading.value = false;
    }
}
</script>

<template>
    <Head title="Relatório de Propostas Finalizadas" />
    <div class="space-y-4">
        <div class="border-b border-border pb-4">
            <h2 class="text-3xl font-bold tracking-tight text-foreground">Propostas Finalizadas</h2>
            <p class="text-sm text-muted-foreground">Propostas com a timeline concluída e o documento da etapa final.</p>
        </div>

        <ReportFilters :url="listUrl" :filters="filters" :developments="developments" />

        <div
            v-if="pageFullySelected && !allMatching && downloadableCount > selectableOnPage.length"
            class="rounded-md border border-border bg-muted/40 px-4 py-2 text-center text-sm"
        >
            Os {{ selectableOnPage.length }} documentos desta página estão selecionados.
            <button type="button" class="font-medium text-primary underline" @click="allMatching = true">
                Selecionar todos os {{ downloadableCount }} resultados do filtro
            </button>
        </div>
        <div v-else-if="allMatching" class="rounded-md border border-border bg-muted/40 px-4 py-2 text-center text-sm">
            Todos os {{ downloadableCount }} documentos do filtro estão selecionados.
            <button type="button" class="font-medium text-primary underline" @click="clearSelection">Limpar seleção</button>
        </div>

        <ZipDownloadBar
            v-if="selectedCount > 0"
            :count="selectedCount"
            :limits="zipLimits"
            :downloading="downloading"
            @download="download"
            @clear="clearSelection"
        />

        <ServerDataTable
            :columns="columns"
            :paginator="proposals"
            :url="listUrl"
            :extra-params="extraParams"
            :with-trashed-filter="false"
            :with-search="false"
            empty-text="Nenhuma proposta finalizada no período."
        />
    </div>
</template>
