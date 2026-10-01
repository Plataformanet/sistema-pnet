<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import StageForm from "../components/StageForm.vue";
import type { Stage } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    stage: Stage;
    hasOpenProposalStages?: boolean;
}>();

const form = useForm({
    name: props.stage.name || "",
    order: props.stage.order ?? 0,
    has_date: props.stage.has_date ?? false,
    date_required: props.stage.date_required ?? false,
    has_upload: props.stage.has_upload ?? false,
    upload_required: props.stage.upload_required ?? false,
    title_required: props.stage.title_required ?? false,
    notes_required: props.stage.notes_required ?? false,
    completion_deadline_hours: props.stage.completion_deadline_hours ?? 0,
    alert_deadline_hours: props.stage.alert_deadline_hours ?? 0,
    shows_property_data: props.stage.shows_property_data ?? false,
    shows_registry_protocol: props.stage.shows_registry_protocol ?? false,
});

function submit() {
    form.put(route('tenant.documents.stages.update', props.stage.id));
}
</script>

<template>
    <Head title="Editar Etapa" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Etapa
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.stages.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <StageForm :form="form" @submit="submit" submitText="Atualizar Etapa">
            <template #before>
                <div
                    v-if="props.hasOpenProposalStages"
                    class="rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100"
                >
                    Esta etapa está em andamento em propostas. As regras de
                    obrigatoriedade alteradas aqui passam a valer imediatamente
                    para a conclusão dessas etapas.
                </div>
            </template>
        </StageForm>
    </div>
</template>
