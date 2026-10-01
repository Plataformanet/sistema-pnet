<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
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
import { CheckCircle2, Circle, Download, Trash } from "lucide-vue-next";
import { router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import ConfirmDeleteDialog from "./ConfirmDeleteDialog.vue";
import { route } from "ziggy-js";
import type { ChecklistItem, Option, Proposal, ProposalAbilities } from "@/types";

const props = defineProps<{
    proposal: Proposal;
    checklist: ChecklistItem[];
    documentTypes: Record<string, Option[]>;
    maxUploadKb: number;
    can: ProposalAbilities;
}>();

const ownerLabels: Record<string, string> = {
    buyer: "Comprador",
    seller: "Vendedor",
    property: "Imóvel",
    stage: "Etapa",
    general: "Geral",
};

const form = useForm({
    owner: "buyer",
    person_id: null as number | null,
    type: "",
    title: "",
    files: [] as File[],
});

const people = computed(() => {
    if (form.owner === "buyer") {
        return (props.proposal.applicants ?? []).map((applicant) => ({ id: applicant.id, name: applicant.contact.name_corporatereason }));
    }

    if (form.owner === "seller") {
        return (props.proposal.sellers ?? []).map((seller) => ({ id: seller.id, name: seller.contact.name_corporatereason, type: seller.contact.type }));
    }

    return [];
});

const typeOptions = computed(() => {
    if (form.owner === "seller") {
        const seller = (props.proposal.sellers ?? []).find((item) => item.id === form.person_id);

        return props.documentTypes[seller?.contact.type === "PJ" ? "seller_pj" : "seller_pf"] ?? [];
    }

    return props.documentTypes[form.owner] ?? [];
});

function onOwnerChange(value: string) {
    form.owner = value;
    form.person_id = null;
    form.type = "";
}

function onFiles(event: Event) {
    form.files = Array.from((event.target as HTMLInputElement).files ?? []);
}

function submit() {
    form.post(route("tenant.documents.proposals.documents.store", props.proposal.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset("files", "title", "type"),
    });
}

const pendingDelete = ref<number | null>(null);

function destroy() {
    if (pendingDelete.value === null) {
        return;
    }

    router.delete(route("tenant.documents.proposals.documents.destroy", [props.proposal.id, pendingDelete.value]), {
        preserveScroll: true,
        onFinish: () => (pendingDelete.value = null),
    });
}

const formatSize = (bytes: number) => `${(bytes / 1024).toFixed(0)} KB`;
</script>

<template>
    <div class="space-y-8">
        <div>
            <h3 class="mb-4 text-lg font-semibold text-card-foreground">Documentos obrigatórios</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div v-for="item in checklist" :key="`${item.owner}-${item.person_id}`" class="rounded-md border border-border p-4">
                    <p class="mb-2 font-medium">
                        {{ ownerLabels[item.owner] }}: {{ item.name }}
                        <span v-if="item.complete" class="ml-1 text-xs font-semibold text-green-600">(completo)</span>
                    </p>
                    <ul class="space-y-1 text-sm">
                        <li v-for="type in item.required" :key="type.value" class="flex items-center gap-2">
                            <CheckCircle2 v-if="type.sent" class="h-4 w-4 text-green-600" />
                            <Circle v-else class="h-4 w-4 text-muted-foreground" />
                            {{ type.label }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <form v-if="can.uploadDocument" @submit.prevent="submit" class="space-y-4 rounded-md border border-border p-4">
            <h3 class="text-lg font-semibold text-card-foreground">Enviar documentos</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <Field>
                    <FieldLabel for="document_owner">Pertence a *</FieldLabel>
                    <Select :model-value="form.owner" @update:model-value="onOwnerChange(String($event))">
                        <SelectTrigger id="document_owner">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="buyer">Comprador</SelectItem>
                                <SelectItem value="seller">Vendedor</SelectItem>
                                <SelectItem value="property">Imóvel</SelectItem>
                                <SelectItem value="general">Geral</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.owner">{{ form.errors.owner }}</FieldError>
                </Field>
                <Field v-if="people.length || form.owner === 'buyer' || form.owner === 'seller'">
                    <FieldLabel for="document_person">Pessoa *</FieldLabel>
                    <Select
                        :model-value="form.person_id ? String(form.person_id) : ''"
                        @update:model-value="form.person_id = Number($event)"
                    >
                        <SelectTrigger id="document_person">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="person in people" :key="person.id" :value="String(person.id)">{{
                                    person.name
                                }}</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.person_id">{{ form.errors.person_id }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="document_type">Tipo</FieldLabel>
                    <Select :model-value="form.type" @update:model-value="form.type = String($event)">
                        <SelectTrigger id="document_type">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="type in typeOptions" :key="type.value" :value="type.value">{{
                                    type.label
                                }}</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.type">{{ form.errors.type }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="document_title">Título</FieldLabel>
                    <Input id="document_title" v-model="form.title" placeholder="Opcional" />
                </Field>
                <Field class="md:col-span-4">
                    <FieldLabel for="document_files">Arquivos *</FieldLabel>
                    <Input id="document_files" type="file" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.bmp,.zip" @change="onFiles" />
                    <FieldDescription>PDF, DOC, DOCX, JPG, PNG, BMP ou ZIP — até {{ Math.round(maxUploadKb / 1024) }} MB por arquivo.</FieldDescription>
                    <FieldError v-if="form.errors.files">{{ form.errors.files }}</FieldError>
                    <FieldError v-for="(message, key) in form.errors" v-show="String(key).startsWith('files.')" :key="key">{{ message }}</FieldError>
                </Field>
            </div>
            <div class="flex justify-end">
                <Button type="submit" :disabled="form.processing || !form.files.length">Enviar</Button>
            </div>
        </form>

        <div>
            <h3 class="mb-4 text-lg font-semibold text-card-foreground">Arquivos enviados</h3>
            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Título</TableHead>
                            <TableHead>Pertence a</TableHead>
                            <TableHead>Arquivo</TableHead>
                            <TableHead>Tamanho</TableHead>
                            <TableHead>Enviado em</TableHead>
                            <TableHead></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="document in proposal.documents" :key="document.id">
                            <TableCell>{{ document.title }}</TableCell>
                            <TableCell>{{ ownerLabels[document.owner] }}</TableCell>
                            <TableCell>{{ document.original_name }}</TableCell>
                            <TableCell>{{ formatSize(document.size) }}</TableCell>
                            <TableCell>{{ new Date(document.created_at).toLocaleDateString("pt-BR") }}</TableCell>
                            <TableCell class="flex justify-end gap-1">
                                <Button variant="ghost" size="sm" as-child>
                                    <a :href="route('tenant.documents.proposals.documents.download', [proposal.id, document.id])">
                                        <Download class="h-4 w-4" />
                                    </a>
                                </Button>
                                <Button v-if="can.deleteDocument" variant="ghost" size="sm" class="text-red-600" @click="pendingDelete = document.id">
                                    <Trash class="h-4 w-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="!proposal.documents?.length">
                            <TableCell colspan="6" class="h-16 text-center">Nenhum documento enviado.</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <ConfirmDeleteDialog
            :open="pendingDelete !== null"
            title="Excluir documento?"
            description="O arquivo será apagado definitivamente."
            @update:open="(value) => !value && (pendingDelete = null)"
            @confirm="destroy"
        />
    </div>
</template>
