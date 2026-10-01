<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import StageForm from "../components/StageForm.vue";

defineOptions({ layout: TenantLayout });

const form = useForm({
    name: "",
    order: 1,
    has_date: false,
    date_required: false,
    has_upload: false,
    upload_required: false,
    title_required: false,
    notes_required: false,
    completion_deadline_hours: 0,
    alert_deadline_hours: 0,
    shows_property_data: false,
    shows_registry_protocol: false,
});

function submit() {
    form.post(route('tenant.documents.stages.store'));
}
</script>

<template>
    <Head title="Nova Etapa" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Nova Etapa
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.stages.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <StageForm :form="form" @submit="submit" />
    </div>
</template>
