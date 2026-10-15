<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import RegistryProtocolForm from "../components/RegistryProtocolForm.vue";
import type { RegistryProtocolFormData, RegistryProtocolPositionOption } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    protocol: RegistryProtocolFormData;
    positions: RegistryProtocolPositionOption[];
}>();

const form = useForm({
    proposal_id: props.protocol.proposal_id as number | string | null,
    protocol_number: props.protocol.protocol_number || "",
    buyer_name: props.protocol.buyer_name || "",
    seller_name: props.protocol.seller_name || "",
    entered_at: props.protocol.entered_at || "",
    exited_at: props.protocol.exited_at || "",
    position: props.protocol.position,
});

function submit() {
    form.put(route("tenant.documents.registry-protocols.update", props.protocol.id));
}
</script>

<template>
    <Head title="Editar Protocolo de Registro" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">Editar Protocolo de Registro</h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.registry-protocols.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <RegistryProtocolForm
            :form="form"
            :positions="positions"
            :initial-proposal="protocol.proposal"
            submit-text="Atualizar Protocolo"
            @submit="submit"
        />
    </div>
</template>
