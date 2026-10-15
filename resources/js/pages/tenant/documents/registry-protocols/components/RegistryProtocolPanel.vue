<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { FileStack, Pencil, Plus } from "lucide-vue-next";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { usePermission } from "@/composables/usePermission";
import PositionBadge from "./PositionBadge.vue";
import type { RegistryProtocolPanelData } from "@/types";

defineProps<{
    proposalId: number;
    protocol: RegistryProtocolPanelData | null;
}>();

const { permissions } = usePermission();

const formatDate = (date: string) => new Date(`${date}T00:00:00`).toLocaleDateString("pt-BR");
</script>

<template>
    <div class="mt-3 rounded-md border border-dashed border-border bg-muted/30 p-3 text-sm">
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
            <span class="flex items-center gap-1 font-medium text-foreground">
                <FileStack class="h-4 w-4" /> Protocolo de registro
            </span>
            <Button
                v-if="protocol && permissions.includes('documents.registry_protocols.edit')"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="route('tenant.documents.registry-protocols.edit', protocol.id)">
                    <Pencil class="mr-1 h-3.5 w-3.5" /> Editar protocolo
                </Link>
            </Button>
            <Button
                v-else-if="!protocol && permissions.includes('documents.registry_protocols.create')"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="route('tenant.documents.registry-protocols.create', { proposal_id: proposalId })">
                    <Plus class="mr-1 h-3.5 w-3.5" /> Cadastrar protocolo
                </Link>
            </Button>
        </div>

        <p v-if="!protocol" class="text-muted-foreground">Nenhum protocolo de registro cadastrado.</p>

        <dl v-else class="grid grid-cols-1 gap-x-6 gap-y-1 sm:grid-cols-2">
            <div><dt class="inline text-muted-foreground">Protocolo: </dt><dd class="inline">{{ protocol.protocol_number }}</dd></div>
            <div><dt class="inline text-muted-foreground">Posição: </dt><dd class="inline"><PositionBadge :position="protocol.position" /></dd></div>
            <div><dt class="inline text-muted-foreground">Comprador: </dt><dd class="inline">{{ protocol.buyer_name }}</dd></div>
            <div v-if="protocol.seller_name"><dt class="inline text-muted-foreground">Vendedor: </dt><dd class="inline">{{ protocol.seller_name }}</dd></div>
            <div><dt class="inline text-muted-foreground">Entrada: </dt><dd class="inline">{{ formatDate(protocol.entered_at) }}</dd></div>
            <div v-if="protocol.exited_at"><dt class="inline text-muted-foreground">Saída: </dt><dd class="inline">{{ formatDate(protocol.exited_at) }}</dd></div>
        </dl>

        <p v-if="protocol && protocol.previous_count > 0" class="mt-2">
            <Link
                v-if="permissions.includes('documents.registry_protocols.view')"
                :href="route('tenant.documents.registry-protocols.list', { status: 'all', proposal_id: proposalId })"
                class="text-primary underline"
            >
                +{{ protocol.previous_count }} anterior{{ protocol.previous_count > 1 ? "es" : "" }}
            </Link>
            <span v-else class="text-muted-foreground">
                +{{ protocol.previous_count }} anterior{{ protocol.previous_count > 1 ? "es" : "" }}
            </span>
        </p>
    </div>
</template>
