<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
import MoneyInput from "@/components/MoneyInput.vue";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { FileText, Trash } from "lucide-vue-next";
import ConfirmDeleteDialog from "./ConfirmDeleteDialog.vue";
import { router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { route } from "ziggy-js";
import { formatMoney, handleMask, maskCNPJ, maskCPF } from "@/lib/masks";
import type { Proposal, ProposalAbilities } from "@/types";

const props = defineProps<{
    proposal: Proposal;
    can: ProposalAbilities;
}>();

const typeLabels: Record<string, string> = {
    general: "Geral",
    advisory: "Assessoria",
    courier: "Motoboy",
};

/**
 * Cobranças que geram recibo: lançamento manual cujo tipo de custo gera
 * recibo (assessoria/motoboy) ou serviço marcado para gerar recibo.
 */
const receiptableItems = computed(() =>
    (props.proposal.cost_items ?? []).filter((item) =>
        item.type === "manual" ? !!item.cost_type?.receipt_type : item.generates_receipt,
    ),
);

const form = useForm({
    proposal_cost_item_id: null as number | null,
    name: "",
    document: "",
    registration_number: "",
    total_spent: null as number | null,
    amount_deposited: null as number | null,
    date: new Date().toISOString().slice(0, 10),
});

const isGeneral = computed(() => form.proposal_cost_item_id === null);

function submit() {
    form.post(route("tenant.documents.proposals.receipts.store", props.proposal.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

const pendingDelete = ref<number | null>(null);

function destroy() {
    if (pendingDelete.value === null) {
        return;
    }

    router.delete(route("tenant.documents.proposals.receipts.destroy", [props.proposal.id, pendingDelete.value]), {
        preserveScroll: true,
        onFinish: () => (pendingDelete.value = null),
    });
}

const maskDocument = (value: string) => (value.replace(/\D/g, "").length > 11 ? maskCNPJ(value) : maskCPF(value));
</script>

<template>
    <div class="space-y-8">
        <form v-if="can.manageFinancial" @submit.prevent="submit" class="space-y-4 rounded-md border border-border p-4">
            <h3 class="text-lg font-semibold text-card-foreground">Novo recibo</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Field class="md:col-span-3">
                    <FieldLabel for="receipt_cost_item">Recibo de</FieldLabel>
                    <Select
                        :model-value="form.proposal_cost_item_id ? String(form.proposal_cost_item_id) : 'general'"
                        @update:model-value="form.proposal_cost_item_id = $event === 'general' ? null : Number($event)"
                    >
                        <SelectTrigger id="receipt_cost_item">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="general">Geral (todas as cobranças)</SelectItem>
                                <SelectItem v-for="item in receiptableItems" :key="item.id" :value="String(item.id)">
                                    {{ item.description ?? item.cost_type?.name }} — {{ formatMoney(item.amount) }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </Field>
                <Field>
                    <FieldLabel for="receipt_name">Nome *</FieldLabel>
                    <Input id="receipt_name" v-model="form.name" />
                    <FieldError v-if="form.errors.name">{{ form.errors.name }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="receipt_document">CPF/CNPJ *</FieldLabel>
                    <Input
                        id="receipt_document"
                        :model-value="form.document"
                        @input="(e: Event) => handleMask(e, maskDocument, (val) => (form.document = val))"
                    />
                    <FieldError v-if="form.errors.document">{{ form.errors.document }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="receipt_registration">Matrícula</FieldLabel>
                    <Input id="receipt_registration" v-model="form.registration_number" />
                </Field>
                <template v-if="isGeneral">
                    <Field>
                        <FieldLabel for="receipt_total_spent">Total gasto *</FieldLabel>
                        <MoneyInput id="receipt_total_spent" v-model="form.total_spent" nullable />
                        <FieldError v-if="form.errors.total_spent">{{ form.errors.total_spent }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="receipt_amount_deposited">Valor depositado *</FieldLabel>
                        <MoneyInput id="receipt_amount_deposited" v-model="form.amount_deposited" nullable />
                        <FieldError v-if="form.errors.amount_deposited">{{ form.errors.amount_deposited }}</FieldError>
                    </Field>
                </template>
                <Field>
                    <FieldLabel for="receipt_date">Data *</FieldLabel>
                    <Input id="receipt_date" v-model="form.date" type="date" />
                    <FieldError v-if="form.errors.date">{{ form.errors.date }}</FieldError>
                </Field>
            </div>
            <div class="flex justify-end">
                <Button type="submit" :disabled="form.processing">Gerar recibo</Button>
            </div>
        </form>

        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Nome</TableHead>
                        <TableHead>Cartório</TableHead>
                        <TableHead>Data</TableHead>
                        <TableHead class="text-right">Valor</TableHead>
                        <TableHead></TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="receipt in proposal.receipts" :key="receipt.id">
                        <TableCell>{{ typeLabels[receipt.type] }}</TableCell>
                        <TableCell>{{ receipt.name }}</TableCell>
                        <TableCell>{{ receipt.notary_name ?? "—" }}</TableCell>
                        <TableCell>{{ new Date(`${receipt.date}T00:00:00`).toLocaleDateString("pt-BR") }}</TableCell>
                        <TableCell class="text-right">{{ formatMoney(receipt.total_spent) }}</TableCell>
                        <TableCell class="whitespace-nowrap text-right">
                            <Button variant="ghost" size="sm" as-child>
                                <a :href="route('tenant.documents.proposals.receipts.pdf', [proposal.id, receipt.id])" target="_blank">
                                    <FileText class="h-4 w-4" />
                                </a>
                            </Button>
                            <Button v-if="can.manageFinancial" variant="ghost" size="sm" class="text-red-600" @click="pendingDelete = receipt.id">
                                <Trash class="h-4 w-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="!proposal.receipts?.length">
                        <TableCell colspan="6" class="h-16 text-center">Nenhum recibo emitido.</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <ConfirmDeleteDialog
            :open="pendingDelete !== null"
            title="Excluir recibo?"
            description="O recibo será excluído definitivamente."
            @update:open="(value) => !value && (pendingDelete = null)"
            @confirm="destroy"
        />
    </div>
</template>
