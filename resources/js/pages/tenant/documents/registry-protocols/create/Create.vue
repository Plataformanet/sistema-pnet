<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import axios from "axios";
import { onMounted } from "vue";
import RegistryProtocolForm from "../components/RegistryProtocolForm.vue";
import type { RegistryProtocolPositionOption, RegistryProtocolProposalOption } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    positions: RegistryProtocolPositionOption[];
    proposal: RegistryProtocolProposalOption | null;
}>();

const form = useForm({
    proposal_id: props.proposal?.id ?? (null as number | string | null),
    protocol_number: "",
    buyer_name: "",
    seller_name: "",
    entered_at: "",
    exited_at: "",
    position: props.positions[0]?.value ?? (null as number | null),
});

// Vindo da timeline ("Cadastrar protocolo"), a proposta já chega selecionada:
// preenche comprador e vendedor como se ela tivesse sido escolhida no campo.
onMounted(async () => {
    if (!props.proposal) {
        return;
    }

    try {
        const { data } = await axios.get<{ buyer_name: string; seller_name: string | null }>(
            route("tenant.documents.registry-protocols.proposals.parties", props.proposal.id),
        );

        form.buyer_name ||= data.buyer_name;
        form.seller_name ||= data.seller_name ?? "";
    } catch {
        // Sem sugestão: o usuário digita os nomes.
    }
});

function submit() {
    form.post(route("tenant.documents.registry-protocols.store"));
}
</script>

<template>
    <Head title="Novo Protocolo de Registro" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">Novo Protocolo de Registro</h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.registry-protocols.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <RegistryProtocolForm :form="form" :positions="positions" :initial-proposal="proposal" @submit="submit" />
    </div>
</template>
