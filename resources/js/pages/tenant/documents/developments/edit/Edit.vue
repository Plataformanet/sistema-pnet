<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import DevelopmentForm from "../components/DevelopmentForm.vue";
import type { Development } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    development: Development;
}>();

const form = useForm({
    name: props.development.name || "",
});

function submit() {
    form.put(route('tenant.documents.developments.update', props.development.id));
}
</script>

<template>
    <Head title="Editar Empreendimento" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Empreendimento
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.developments.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <DevelopmentForm :form="form" @submit="submit" submitText="Atualizar Empreendimento" />
    </div>
</template>
