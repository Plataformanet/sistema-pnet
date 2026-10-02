<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
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
import BankAccountFields from "./BankAccountFields.vue";
import ConfirmDeleteDialog from "./ConfirmDeleteDialog.vue";
import { handleMask, maskCNPJ, maskCPF, maskPhone } from "@/lib/masks";
import { Pencil, Trash, UserPen, UserPlus } from "lucide-vue-next";
import { router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { route } from "ziggy-js";
import type { Option, SellerFormData } from "@/types";

/**
 * Vendedores da proposta em edição: a tabela lista todos, o botão de editar
 * abre a seção com os dados do vendedor escolhido e "Adicionar" abre a mesma
 * seção vazia. Vendedor já cadastrado com o mesmo documento é reaproveitado.
 * Tipo de pessoa e documento não são editáveis depois de incluídos.
 */
const props = defineProps<{
    proposalId: number;
    sellers: SellerFormData[];
    personTypes: Option[];
    maritalStatuses: Option<number>[];
    bankAccountTypes: Option<number>[];
}>();

const emptyBankAccount = () => ({ bank_name: "", account_type: undefined as number | undefined, branch: "", number: "", notes: "" });

const emptySeller = () => ({
    person_type: "PF",
    document: "",
    name: "",
    email: "",
    phone: "",
    marital_status: null as number | null,
    profession: "",
    declared_income: null as number | null,
    declares_income_tax: false,
    income_tax_notes: "",
    by_power_of_attorney: false,
    bank_account: emptyBankAccount(),
});

const mode = ref<"create" | "edit" | null>(null);
const editingId = ref<number | null>(null);

const form = useForm(emptySeller());

const yesNoFields: Array<{ name: "declares_income_tax" | "by_power_of_attorney"; label: string }> = [
    { name: "declares_income_tax", label: "Declara IR?" },
    { name: "by_power_of_attorney", label: "Por procuração?" },
];

const maskDocument = (personType: string, value: string) => (personType === "PJ" ? maskCNPJ(value) : maskCPF(value));
const personTypeLabel = (value: string) => props.personTypes.find((type) => type.value === value)?.label ?? value;

function open(nextMode: "create" | "edit", data: ReturnType<typeof emptySeller>, id: number | null = null) {
    mode.value = nextMode;
    editingId.value = id;
    form.clearErrors();
    Object.assign(form, data);
}

function create() {
    open("create", emptySeller());
}

function edit(seller: SellerFormData) {
    open(
        "edit",
        {
            person_type: seller.person_type,
            document: maskDocument(seller.person_type, seller.document),
            name: seller.name,
            email: seller.email,
            phone: seller.phone ? maskPhone(seller.phone) : "",
            marital_status: seller.marital_status,
            profession: seller.profession ?? "",
            declared_income: seller.declared_income,
            declares_income_tax: seller.declares_income_tax,
            income_tax_notes: seller.income_tax_notes ?? "",
            by_power_of_attorney: seller.by_power_of_attorney,
            bank_account: { ...emptyBankAccount(), ...(seller.bank_account ?? {}), notes: seller.bank_account?.notes ?? "" },
        },
        seller.id,
    );
}

function close() {
    mode.value = null;
    editingId.value = null;
    form.clearErrors();
}

function changePersonType(value: string) {
    form.person_type = value;
    form.document = "";
}

const pendingRemoval = ref<SellerFormData | null>(null);

function remove() {
    const seller = pendingRemoval.value;

    if (seller === null) {
        return;
    }

    router.delete(route("tenant.documents.proposals.sellers.destroy", [props.proposalId, seller.id]), {
        preserveScroll: true,
        onSuccess: () => editingId.value === seller.id && close(),
        onFinish: () => (pendingRemoval.value = null),
    });
}

function submit() {
    const options = { preserveScroll: true, onSuccess: close };

    if (mode.value === "create") {
        form.transform((data) => data).post(route("tenant.documents.proposals.sellers.store", props.proposalId), options);

        return;
    }

    if (editingId.value !== null) {
        form.transform(({ person_type, document, ...data }) => data).put(
            route("tenant.documents.proposals.sellers.update", [props.proposalId, editingId.value]),
            options,
        );
    }
}
</script>

<template>
    <div class="space-y-6 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-lg font-semibold text-card-foreground">Vendedor(es)</h3>
            <Button v-if="mode === null" type="button" variant="outline" @click="create">
                <UserPlus class="mr-1 h-4 w-4" /> Adicionar vendedor
            </Button>
        </div>

        <form v-if="mode !== null" class="space-y-6" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:col-span-2">
                    <Field>
                        <FieldLabel for="seller-edit-person_type">Tipo de pessoa *</FieldLabel>
                        <Select
                            :model-value="form.person_type"
                            :disabled="mode === 'edit'"
                            @update:model-value="changePersonType(String($event))"
                        >
                            <SelectTrigger id="seller-edit-person_type">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem v-for="type in personTypes" :key="type.value" :value="type.value">{{
                                        type.label
                                    }}</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FieldError v-if="form.errors.person_type">{{ form.errors.person_type }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="seller-edit-document">{{ form.person_type === "PJ" ? "CNPJ" : "CPF" }} *</FieldLabel>
                        <Input
                            id="seller-edit-document"
                            :model-value="form.document"
                            :disabled="mode === 'edit'"
                            @input="(e: Event) => handleMask(e, form.person_type === 'PJ' ? maskCNPJ : maskCPF, (val) => (form.document = val))"
                        />
                        <FieldError v-if="form.errors.document">{{ form.errors.document }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="seller-edit-name">{{ form.person_type === "PJ" ? "Razão social" : "Nome" }} *</FieldLabel>
                        <Input id="seller-edit-name" v-model="form.name" />
                        <FieldError v-if="form.errors.name">{{ form.errors.name }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="seller-edit-email">E-mail *</FieldLabel>
                        <Input id="seller-edit-email" v-model="form.email" type="email" />
                        <FieldError v-if="form.errors.email">{{ form.errors.email }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="seller-edit-phone">Telefone</FieldLabel>
                        <Input
                            id="seller-edit-phone"
                            :model-value="form.phone"
                            @input="(e: Event) => handleMask(e, maskPhone, (val) => (form.phone = val))"
                        />
                        <FieldError v-if="form.errors.phone">{{ form.errors.phone }}</FieldError>
                    </Field>

                    <template v-if="form.person_type === 'PF'">
                        <Field>
                            <FieldLabel for="seller-edit-marital_status">Estado civil</FieldLabel>
                            <Select
                                :model-value="form.marital_status ? String(form.marital_status) : ''"
                                @update:model-value="form.marital_status = Number($event)"
                            >
                                <SelectTrigger id="seller-edit-marital_status">
                                    <SelectValue placeholder="Selecione..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem
                                            v-for="status in maritalStatuses"
                                            :key="status.value"
                                            :value="String(status.value)"
                                            >{{ status.label }}</SelectItem
                                        >
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <FieldError v-if="form.errors.marital_status">{{ form.errors.marital_status }}</FieldError>
                        </Field>
                        <Field>
                            <FieldLabel for="seller-edit-profession">Profissão</FieldLabel>
                            <Input id="seller-edit-profession" v-model="form.profession" />
                            <FieldError v-if="form.errors.profession">{{ form.errors.profession }}</FieldError>
                        </Field>
                        <Field>
                            <FieldLabel for="seller-edit-declared_income">Renda declarada</FieldLabel>
                            <MoneyInput id="seller-edit-declared_income" v-model="form.declared_income" nullable />
                            <FieldError v-if="form.errors.declared_income">{{ form.errors.declared_income }}</FieldError>
                        </Field>
                        <Field v-for="field in yesNoFields" :key="field.name">
                            <FieldLabel :for="`seller-edit-${field.name}`">{{ field.label }}</FieldLabel>
                            <Select
                                :model-value="form[field.name] ? '1' : '0'"
                                @update:model-value="form[field.name] = $event === '1'"
                            >
                                <SelectTrigger :id="`seller-edit-${field.name}`">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="1">Sim</SelectItem>
                                        <SelectItem value="0">Não</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </Field>
                        <Field class="md:col-span-2">
                            <FieldLabel for="seller-edit-income_tax_notes">Observações sobre o imposto de renda</FieldLabel>
                            <Textarea id="seller-edit-income_tax_notes" v-model="form.income_tax_notes" />
                        </Field>
                    </template>
                </div>

                <div class="space-y-4">
                    <p class="text-sm font-medium text-muted-foreground">Conta bancária para crédito (opcional)</p>
                    <BankAccountFields
                        :account="form.bank_account"
                        :account-types="bankAccountTypes"
                        :errors="form.errors"
                        error-prefix="bank_account"
                        id-prefix="seller-edit"
                        stacked
                    />
                    <Field>
                        <FieldLabel for="seller-edit-bank_notes">Observações sobre os dados bancários</FieldLabel>
                        <Textarea id="seller-edit-bank_notes" v-model="form.bank_account.notes" />
                    </Field>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button type="submit" :loading="form.processing" :disabled="form.processing">
                    <template v-if="mode === 'create'"><UserPlus class="mr-1 h-4 w-4" /> Adicionar vendedor</template>
                    <template v-else><UserPen class="mr-1 h-4 w-4" /> Atualizar vendedor</template>
                </Button>
                <Button type="button" variant="outline" :disabled="form.processing" @click="close">Cancelar</Button>
            </div>
        </form>

        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nome / razão social</TableHead>
                        <TableHead>Tipo</TableHead>
                        <TableHead>CPF/CNPJ</TableHead>
                        <TableHead>Telefone</TableHead>
                        <TableHead>E-mail</TableHead>
                        <TableHead class="text-right">Ações</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="seller in sellers" :key="seller.id" :class="{ 'bg-muted/50': editingId === seller.id }">
                        <TableCell>{{ seller.name }}</TableCell>
                        <TableCell>{{ personTypeLabel(seller.person_type) }}</TableCell>
                        <TableCell>{{ maskDocument(seller.person_type, seller.document) }}</TableCell>
                        <TableCell>{{ seller.phone ? maskPhone(seller.phone) : "—" }}</TableCell>
                        <TableCell>{{ seller.email }}</TableCell>
                        <TableCell class="whitespace-nowrap text-right">
                            <Button type="button" variant="ghost" size="sm" title="Editar vendedor" @click="edit(seller)">
                                <Pencil class="h-4 w-4" />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="text-red-600"
                                title="Remover vendedor"
                                @click="pendingRemoval = seller"
                            >
                                <Trash class="h-4 w-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="!sellers.length">
                        <TableCell colspan="6" class="h-16 text-center">Nenhum vendedor.</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <ConfirmDeleteDialog
            :open="pendingRemoval !== null"
            title="Remover vendedor?"
            :description="`${pendingRemoval?.name ?? 'O vendedor'} será desvinculado desta proposta. O cadastro dele e os documentos já enviados são mantidos.`"
            @update:open="(value) => !value && (pendingRemoval = null)"
            @confirm="remove"
        />
    </div>
</template>
