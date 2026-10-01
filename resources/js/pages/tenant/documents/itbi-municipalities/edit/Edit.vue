<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import MunicipalityForm from "../components/MunicipalityForm.vue";
import type { ItbiModuleOption, ItbiMunicipality, SupportedStateOption } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    municipality: ItbiMunicipality;
    states: SupportedStateOption[];
    modules: ItbiModuleOption[];
}>();

const form = useForm({
    name: props.municipality.name,
    state: props.municipality.state as string | null,
    ibge_code: props.municipality.ibge_code as number | null,
    module: props.municipality.module,
});

function submit() {
    form.put(route("tenant.documents.itbi-municipalities.update", props.municipality.id));
}
</script>

<template>
    <Head title="Editar Município de ITBI" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <h2 class="text-3xl font-bold tracking-tight text-foreground">Editar Município de ITBI</h2>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.itbi-municipalities.list')"><ChevronLeft class="mr-2 h-4 w-4" /> Voltar</Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <MunicipalityForm :form="form" :states="states" :modules="modules" submit-text="Atualizar Município" is-edit @submit="submit" />
    </div>
</template>
