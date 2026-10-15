<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
import ComboboxRemote from "@/components/ui/combobox/ComboboxRemote.vue";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { AlertTriangle, X } from "lucide-vue-next";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import { computed, ref } from "vue";
import { route } from "ziggy-js";
import { toast } from "vue-sonner";
import type { RegistryProtocolPositionOption, RegistryProtocolProposalOption } from "@/types";

const props = withDefaults(
    defineProps<{
        form: ReturnType<typeof useForm>;
        positions: RegistryProtocolPositionOption[];
        initialProposal?: RegistryProtocolProposalOption | null;
        submitText?: string;
    }>(),
    {
        initialProposal: null,
        submitText: "Salvar Protocolo",
    },
);

const emit = defineEmits(["submit"]);

const loadingParties = ref(false);

// PR-D6: a data de saída é opcional, mas a tela avisa ao encerrar sem ela.
const missingExitDate = computed(
    () => !props.form.exited_at && props.positions.some((position) => position.closed && position.value === Number(props.form.position)),
);

/**
 * Ao escolher a proposta, sugere comprador e vendedor com todos os
 * proponentes e vendedores dela. Os campos continuam editáveis.
 */
async function onProposalSelected(item: { id: string | number } | null) {
    if (!item) {
        return;
    }

    loadingParties.value = true;

    try {
        const { data } = await axios.get<{ buyer_name: string; seller_name: string | null }>(
            route("tenant.documents.registry-protocols.proposals.parties", item.id),
        );

        props.form.buyer_name = data.buyer_name;
        props.form.seller_name = data.seller_name ?? "";
    } catch {
        toast.error("Não foi possível buscar o comprador e o vendedor da proposta.");
    } finally {
        loadingParties.value = false;
    }
}

function clearProposal() {
    props.form.proposal_id = null;
}
</script>

<template>
    <form
        @submit.prevent="emit('submit')"
        class="space-y-8 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8"
    >
        <div>
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">Dados do Protocolo</h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="proposal_id">Proposta</FieldLabel>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <ComboboxRemote
                                id="proposal_id"
                                v-model="form.proposal_id"
                                :url="route('tenant.documents.registry-protocols.proposals.search')"
                                :initial-item="initialProposal"
                                placeholder="Protocolo sem proposta vinculada"
                                search-placeholder="Buscar por nº ou proponente..."
                                no-results-text="Nenhuma proposta encontrada."
                                @select="onProposalSelected"
                            />
                        </div>
                        <Button v-if="form.proposal_id" type="button" variant="outline" size="icon" @click="clearProposal">
                            <span class="sr-only">Remover proposta</span>
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                    <FieldDescription>
                        Opcional. Ao escolher a proposta, o comprador e o vendedor são preenchidos com os nomes dela.
                    </FieldDescription>
                    <FieldError v-if="form.errors.proposal_id">{{ form.errors.proposal_id }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="protocol_number">Nº do protocolo *</FieldLabel>
                    <Input id="protocol_number" v-model="form.protocol_number" maxlength="50" required />
                    <FieldError v-if="form.errors.protocol_number">{{ form.errors.protocol_number }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="position">Posição *</FieldLabel>
                    <Select
                        :model-value="form.position ? String(form.position) : ''"
                        @update:model-value="form.position = Number($event)"
                    >
                        <SelectTrigger id="position">
                            <SelectValue placeholder="Selecione a posição..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem
                                    v-for="position in positions"
                                    :key="position.value"
                                    :value="String(position.value)"
                                >
                                    {{ position.label }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.position">{{ form.errors.position }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="buyer_name">Comprador *</FieldLabel>
                    <Input id="buyer_name" v-model="form.buyer_name" :disabled="loadingParties" required />
                    <FieldError v-if="form.errors.buyer_name">{{ form.errors.buyer_name }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="seller_name">Vendedor</FieldLabel>
                    <Input id="seller_name" v-model="form.seller_name" :disabled="loadingParties" />
                    <FieldError v-if="form.errors.seller_name">{{ form.errors.seller_name }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="entered_at">Data de entrada *</FieldLabel>
                    <Input id="entered_at" v-model="form.entered_at" type="date" required />
                    <FieldError v-if="form.errors.entered_at">{{ form.errors.entered_at }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="exited_at">Data de saída</FieldLabel>
                    <Input id="exited_at" v-model="form.exited_at" type="date" :min="form.entered_at || undefined" />
                    <FieldError v-if="form.errors.exited_at">{{ form.errors.exited_at }}</FieldError>
                </Field>

                <div
                    v-if="missingExitDate"
                    class="flex items-center gap-2 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800 md:col-span-2 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
                >
                    <AlertTriangle class="h-4 w-4 shrink-0" />
                    Informe a data de saída do cartório.
                </div>
            </div>
        </div>

        <div class="flex justify-end border-t border-border pt-6">
            <Button type="submit" :disabled="form.processing">{{ submitText }}</Button>
        </div>
    </form>
</template>
