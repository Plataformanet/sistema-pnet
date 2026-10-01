<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
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
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Check, FileDown, Pencil, Trash, Upload, X } from "lucide-vue-next";
import ConfirmDeleteDialog from "./ConfirmDeleteDialog.vue";
import { router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { route } from "ziggy-js";
import { formatMoney } from "@/lib/masks";
import type { CostType, Notary, Proposal, ProposalAbilities, ProposalCostItem } from "@/types";

const props = defineProps<{
    proposal: Proposal;
    costTypes: Pick<CostType, "id" | "name" | "requires_notary" | "receipt_type">[];
    notaries: Pick<Notary, "id" | "name">[];
    feesTotal: number;
    can: ProposalAbilities;
}>();

const typeLabels: Record<string, string> = {
    emolument: "Emolumento",
    extra_fee: "Taxa extra",
    service: "Serviço",
    itbi: "ITBI",
    manual: "Lançamento manual",
};

const items = computed(() => props.proposal.cost_items ?? []);
const total = computed(() => items.value.reduce((sum, item) => sum + item.amount, 0));

const form = useForm({
    cost_type_id: null as number | null,
    notary_id: null as number | null,
    description: "",
    date: "",
    amount: null as number | null,
    notes: "",
    bill: null as File | null,
    notify_partners: false,
    notify_applicants: false,
});

const selectedCostType = computed(() => props.costTypes.find((type) => type.id === form.cost_type_id));

function submit() {
    form.post(route("tenant.documents.proposals.cost-items.store", props.proposal.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

const editingId = ref<number | null>(null);
const amountForm = useForm({ amount: 0 as number | null });

function editAmount(item: ProposalCostItem) {
    editingId.value = item.id;
    amountForm.amount = item.amount;
    amountForm.clearErrors();
}

function saveAmount(item: ProposalCostItem) {
    amountForm.patch(route("tenant.documents.proposals.cost-items.update", [props.proposal.id, item.id]), {
        preserveScroll: true,
        onSuccess: () => (editingId.value = null),
    });
}

const pendingDelete = ref<number | null>(null);

function destroy() {
    if (pendingDelete.value === null) {
        return;
    }

    router.delete(route("tenant.documents.proposals.cost-items.destroy", [props.proposal.id, pendingDelete.value]), {
        preserveScroll: true,
        onFinish: () => (pendingDelete.value = null),
    });
}

function uploadProof(item: ProposalCostItem, event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    router.post(
        route("tenant.documents.proposals.cost-items.proof.store", [props.proposal.id, item.id]),
        { proof: file },
        { forceFormData: true, preserveScroll: true },
    );
}

const describe = (item: ProposalCostItem) =>
    item.description ?? item.extra_fee_description ?? item.cost_type?.name ?? typeLabels[item.type];
</script>

<template>
    <div class="space-y-8">
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Descrição</TableHead>
                        <TableHead>Cartório</TableHead>
                        <TableHead>Data</TableHead>
                        <TableHead class="text-right">Valor</TableHead>
                        <TableHead>Anexos</TableHead>
                        <TableHead></TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="item in items" :key="item.id">
                        <TableCell>{{ item.type === "manual" ? item.cost_type?.name : typeLabels[item.type] }}</TableCell>
                        <TableCell>
                            {{ describe(item) }}
                            <p v-if="item.notes" class="text-xs text-muted-foreground">{{ item.notes }}</p>
                        </TableCell>
                        <TableCell>{{ item.notary?.name ?? "—" }}</TableCell>
                        <TableCell>{{ item.date ? new Date(`${item.date}T00:00:00`).toLocaleDateString("pt-BR") : "—" }}</TableCell>
                        <TableCell class="text-right">
                            <div v-if="editingId === item.id" class="flex items-center justify-end gap-1">
                                <MoneyInput v-model="amountForm.amount" class="w-36" />
                                <Button size="sm" variant="ghost" :disabled="amountForm.processing" @click="saveAmount(item)"><Check class="h-4 w-4" /></Button>
                                <Button size="sm" variant="ghost" @click="editingId = null"><X class="h-4 w-4" /></Button>
                            </div>
                            <span v-else>{{ formatMoney(item.amount) }}</span>
                            <FieldError v-if="editingId === item.id && amountForm.errors.amount">{{ amountForm.errors.amount }}</FieldError>
                        </TableCell>
                        <TableCell class="space-x-2 whitespace-nowrap text-sm">
                            <a v-if="item.has_bill" :href="route('tenant.documents.proposals.cost-items.bill', [proposal.id, item.id])" class="inline-flex items-center gap-1 text-primary underline">
                                <FileDown class="h-4 w-4" /> Boleto
                            </a>
                            <a v-if="item.has_proof" :href="route('tenant.documents.proposals.cost-items.proof', [proposal.id, item.id])" class="inline-flex items-center gap-1 text-primary underline">
                                <FileDown class="h-4 w-4" /> Comprovante
                            </a>
                            <label v-if="can.uploadProof" class="inline-flex cursor-pointer items-center gap-1 text-muted-foreground hover:text-foreground">
                                <Upload class="h-4 w-4" /> {{ item.has_proof ? "Trocar" : "Anexar" }} comprovante
                                <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png" @change="uploadProof(item, $event)" />
                            </label>
                        </TableCell>
                        <TableCell class="whitespace-nowrap text-right">
                            <Button v-if="can.manageFinancial" variant="ghost" size="sm" @click="editAmount(item)"><Pencil class="h-4 w-4" /></Button>
                            <Button v-if="can.manageFinancial && item.type === 'manual'" variant="ghost" size="sm" class="text-red-600" @click="pendingDelete = item.id">
                                <Trash class="h-4 w-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="!items.length">
                        <TableCell colspan="7" class="h-16 text-center">Nenhum lançamento.</TableCell>
                    </TableRow>
                </TableBody>
                <TableFooter>
                    <TableRow>
                        <TableCell colspan="4">Total de emolumentos</TableCell>
                        <TableCell class="text-right">{{ formatMoney(feesTotal) }}</TableCell>
                        <TableCell colspan="2"></TableCell>
                    </TableRow>
                    <TableRow>
                        <TableCell colspan="4">Total geral</TableCell>
                        <TableCell class="text-right">{{ formatMoney(total) }}</TableCell>
                        <TableCell colspan="2"></TableCell>
                    </TableRow>
                </TableFooter>
            </Table>
        </div>

        <form v-if="can.manageFinancial" @submit.prevent="submit" class="space-y-4 rounded-md border border-border p-4">
            <h3 class="text-lg font-semibold text-card-foreground">Novo lançamento</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Field>
                    <FieldLabel for="cost_type_id">Tipo de custo *</FieldLabel>
                    <Select
                        :model-value="form.cost_type_id ? String(form.cost_type_id) : ''"
                        @update:model-value="
                            form.cost_type_id = Number($event);
                            if (!selectedCostType?.requires_notary) form.notary_id = null;
                        "
                    >
                        <SelectTrigger id="cost_type_id">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="type in costTypes" :key="type.id" :value="String(type.id)">{{ type.name }}</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.cost_type_id">{{ form.errors.cost_type_id }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="notary_id">Cartório{{ selectedCostType?.requires_notary ? " *" : "" }}</FieldLabel>
                    <Select
                        :model-value="form.notary_id ? String(form.notary_id) : ''"
                        @update:model-value="form.notary_id = Number($event)"
                        :disabled="!selectedCostType?.requires_notary"
                    >
                        <SelectTrigger id="notary_id">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="notary in notaries" :key="notary.id" :value="String(notary.id)">{{ notary.name }}</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.notary_id">{{ form.errors.notary_id }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="cost_description">Rubrica *</FieldLabel>
                    <Input id="cost_description" v-model="form.description" />
                    <FieldError v-if="form.errors.description">{{ form.errors.description }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="cost_date">Data</FieldLabel>
                    <Input id="cost_date" v-model="form.date" type="date" />
                    <FieldError v-if="form.errors.date">{{ form.errors.date }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="cost_amount">Valor *</FieldLabel>
                    <MoneyInput id="cost_amount" v-model="form.amount" />
                    <FieldError v-if="form.errors.amount">{{ form.errors.amount }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="cost_bill">Boleto</FieldLabel>
                    <Input id="cost_bill" type="file" accept=".pdf,.jpg,.jpeg,.png" @change="(e: Event) => (form.bill = (e.target as HTMLInputElement).files?.[0] ?? null)" />
                    <FieldError v-if="form.errors.bill">{{ form.errors.bill }}</FieldError>
                </Field>
                <Field class="md:col-span-3">
                    <FieldLabel for="cost_notes">Observação</FieldLabel>
                    <Textarea id="cost_notes" v-model="form.notes" />
                </Field>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap gap-6">
                    <div class="flex items-center gap-2">
                        <Checkbox id="notify_partners" :model-value="form.notify_partners" @update:model-value="form.notify_partners = $event === true" />
                        <Label for="notify_partners" class="cursor-pointer">Notificar parceiro por e-mail</Label>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox id="notify_applicants" :model-value="form.notify_applicants" @update:model-value="form.notify_applicants = $event === true" />
                        <Label for="notify_applicants" class="cursor-pointer">Notificar proponente por e-mail</Label>
                    </div>
                </div>
                <Button type="submit" :disabled="form.processing">Lançar</Button>
            </div>
        </form>

        <ConfirmDeleteDialog
            :open="pendingDelete !== null"
            title="Excluir lançamento?"
            description="O lançamento e seus anexos serão apagados definitivamente."
            @update:open="(value) => !value && (pendingDelete = null)"
            @confirm="destroy"
        />
    </div>
</template>
