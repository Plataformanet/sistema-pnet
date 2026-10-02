<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
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
import { CheckCircle2, Circle, Clock, Download, Play, RotateCcw } from "lucide-vue-next";
import { router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { route } from "ziggy-js";
import type { Proposal, ProposalAbilities, ProposalStage } from "@/types";

const props = defineProps<{
    proposal: Proposal;
    can: ProposalAbilities;
}>();

const stages = computed(() => props.proposal.stages ?? []);
const current = computed(() => stages.value.find((stage) => stage.is_current));
const notStarted = computed(() => !!current.value && !current.value.started_at);
const editing = ref<number | null>(null);
const showRestoreDialog = ref(false);

const form = useForm({
    date: "",
    notes: "",
    title: "",
    file: null as File | null,
});

const statusLabel: Record<ProposalStage["status"], string> = {
    completed: "Concluída",
    in_progress: "Em andamento",
    locked: "Pendente",
};

function open(stage: ProposalStage) {
    editing.value = stage.id;
    form.clearErrors();
    form.date = stage.date ?? "";
    form.notes = stage.notes ?? "";
    form.title = stage.document?.title ?? "";
    form.file = null;
}

function start() {
    router.post(route("tenant.documents.proposals.timeline.start", props.proposal.id), {}, { preserveScroll: true });
}

function submit(stage: ProposalStage) {
    form.post(route("tenant.documents.proposals.timeline.complete", [props.proposal.id, stage.id]), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            editing.value = null;
            form.reset();
        },
    });
}

function restore() {
    router.post(route("tenant.documents.proposals.timeline.restore", props.proposal.id), {}, {
        preserveScroll: true,
        onSuccess: () => (showRestoreDialog.value = false),
    });
}

/**
 * Horas restantes até o prazo de conclusão (0 = vence hoje, < 0 = atrasada).
 */
function remaining(stage: ProposalStage): number | null {
    const deadline = stage.stage.completion_deadline_hours;

    if (!deadline || !stage.started_at || stage.completed_at) {
        return null;
    }

    const elapsed = Math.floor((Date.now() - new Date(stage.started_at).getTime()) / 3_600_000);

    return deadline - elapsed;
}
</script>

<template>
    <div class="space-y-6">
        <div v-if="can.manageTimeline" class="flex flex-wrap gap-2">
            <Button v-if="notStarted" @click="start"><Play class="mr-1 h-4 w-4" /> Iniciar acompanhamento</Button>
            <Button v-if="!notStarted && stages.length" variant="outline" @click="showRestoreDialog = true">
                <RotateCcw class="mr-1 h-4 w-4" /> Restaurar acompanhamento
            </Button>
        </div>

        <p v-if="!stages.length" class="text-sm text-muted-foreground">Esta proposta não possui etapas.</p>

        <ol class="space-y-3">
            <li v-for="stage in stages" :key="stage.id" class="rounded-md border border-border p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <CheckCircle2 v-if="stage.status === 'completed'" class="h-5 w-5 text-green-600" />
                        <Clock v-else-if="stage.status === 'in_progress'" class="h-5 w-5 text-amber-500" />
                        <Circle v-else class="h-5 w-5 text-muted-foreground" />
                        <span class="font-medium">{{ stage.stage.name }}</span>
                        <span class="text-xs text-muted-foreground">({{ statusLabel[stage.status] }})</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-muted-foreground">
                        <span v-if="remaining(stage) !== null" :class="remaining(stage)! < 0 ? 'font-semibold text-red-600' : ''">
                            {{ remaining(stage)! < 0 ? `Atrasada ${Math.abs(remaining(stage)!)}h` : remaining(stage) === 0 ? "Vence hoje" : `Faltam ${remaining(stage)}h` }}
                        </span>
                        <span v-if="stage.completed_at">Concluída em {{ new Date(stage.completed_at).toLocaleDateString("pt-BR") }}</span>
                        <Button
                            v-if="can.manageTimeline && (stage.status === 'completed' || (stage.is_current && stage.started_at))"
                            variant="outline"
                            size="sm"
                            @click="open(stage)"
                        >
                            {{ stage.status === "completed" ? "Editar" : "Concluir etapa" }}
                        </Button>
                    </div>
                </div>

                <div v-if="stage.date || stage.notes || stage.document" class="mt-2 space-y-1 text-sm">
                    <p v-if="stage.date">Data: {{ new Date(`${stage.date}T00:00:00`).toLocaleDateString("pt-BR") }}</p>
                    <p v-if="stage.notes">Observação: {{ stage.notes }}</p>
                    <a
                        v-if="stage.document"
                        :href="route('tenant.documents.proposals.documents.download', [proposal.id, stage.document.id])"
                        class="inline-flex items-center gap-1 text-primary underline"
                    >
                        <Download class="h-4 w-4" /> {{ stage.document.title }}
                    </a>
                </div>

                <div v-if="stage.stage.shows_property_data && proposal.property" class="mt-2 text-sm text-muted-foreground">
                    Imóvel: {{ proposal.property.property_type?.name }} — {{ proposal.property.address }}
                    {{ proposal.property.number }} {{ proposal.property.complement }}
                    <template v-if="proposal.property.development">({{ proposal.property.development.name }} {{ proposal.property.block }} {{ proposal.property.unit }})</template>
                </div>

                <form v-if="editing === stage.id" class="mt-4 grid grid-cols-1 gap-4 border-t border-border pt-4 md:grid-cols-2" @submit.prevent="submit(stage)">
                    <Field v-if="stage.stage.has_date">
                        <FieldLabel :for="`stage-${stage.id}-date`">Data{{ stage.stage.date_required ? " *" : "" }}</FieldLabel>
                        <Input :id="`stage-${stage.id}-date`" v-model="form.date" type="date" />
                        <FieldError v-if="form.errors.date">{{ form.errors.date }}</FieldError>
                    </Field>
                    <template v-if="stage.stage.has_upload">
                        <Field>
                            <FieldLabel :for="`stage-${stage.id}-title`">Título do arquivo{{ stage.stage.title_required && !stage.document ? " *" : "" }}</FieldLabel>
                            <Input :id="`stage-${stage.id}-title`" v-model="form.title" />
                            <FieldError v-if="form.errors.title">{{ form.errors.title }}</FieldError>
                        </Field>
                        <Field>
                            <FieldLabel :for="`stage-${stage.id}-file`">Arquivo{{ stage.stage.upload_required && !stage.document ? " *" : "" }}</FieldLabel>
                            <Input
                                :id="`stage-${stage.id}-file`"
                                type="file"
                                @change="(e: Event) => (form.file = (e.target as HTMLInputElement).files?.[0] ?? null)"
                            />
                            <FieldError v-if="form.errors.file">{{ form.errors.file }}</FieldError>
                        </Field>
                    </template>
                    <Field class="md:col-span-2">
                        <FieldLabel :for="`stage-${stage.id}-notes`">Observação{{ stage.stage.notes_required ? " *" : "" }}</FieldLabel>
                        <Textarea :id="`stage-${stage.id}-notes`" v-model="form.notes" />
                        <FieldError v-if="form.errors.notes">{{ form.errors.notes }}</FieldError>
                    </Field>
                    <div class="flex justify-end gap-2 md:col-span-2">
                        <Button type="button" variant="ghost" @click="editing = null">Cancelar</Button>
                        <Button type="submit" :disabled="form.processing">Salvar</Button>
                    </div>
                </form>
            </li>
        </ol>

        <AlertDialog :open="showRestoreDialog" @update:open="showRestoreDialog = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Restaurar o acompanhamento?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Todas as etapas e os documentos enviados nelas serão apagados, e o acompanhamento
                        voltará a "não iniciado", com a proposta como "Nova Proposta". Esta ação não pode ser desfeita.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="showRestoreDialog = false">Cancelar</AlertDialogCancel>
                    <AlertDialogAction class="bg-red-600 text-white hover:bg-red-700" @click="restore">Restaurar</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
