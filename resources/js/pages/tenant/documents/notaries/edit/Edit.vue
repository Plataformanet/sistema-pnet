<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import { maskCEP } from "@/lib/masks";
import NotaryForm from "../components/NotaryForm.vue";
import type { Notary, Option } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    notary: Notary;
    states: Option[];
}>();

const form = useForm({
    name: props.notary.name || "",
    zip_code: maskCEP(props.notary.zip_code || ""),
    street: props.notary.street || "",
    number: props.notary.number || "",
    complement: props.notary.complement || "",
    neighborhood: props.notary.neighborhood || "",
    city: props.notary.city || "",
    state: props.notary.state || "",
    reference_point: props.notary.reference_point || "",
    business_hours: props.notary.business_hours || "",
});

function submit() {
    form.put(route('tenant.documents.notaries.update', props.notary.id));
}
</script>

<template>
    <Head title="Editar Cartório" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Cartório
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.notaries.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <NotaryForm :form="form" :states="props.states" @submit="submit" submitText="Atualizar Cartório" />
    </div>
</template>
