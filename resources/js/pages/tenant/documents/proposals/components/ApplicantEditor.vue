<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
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
import { handleMask, maskCPF, maskPhone } from "@/lib/masks";
import { Loader2, Pencil, Search, Trash, UserPen, UserPlus } from "lucide-vue-next";
import { router, useForm } from "@inertiajs/vue3";
import axios from "axios";
import { ref } from "vue";
import { route } from "ziggy-js";
import type { ApplicantFormData, Option } from "@/types";

/**
 * Proponentes da proposta em edição: a tabela lista todos, o botão de editar
 * abre a seção com os dados do proponente escolhido e "Adicionar" abre a mesma
 * seção vazia. Na inclusão, a busca pelo CPF reaproveita o proponente já
 * cadastrado (só o CPF é enviado). O CPF não é editável depois de incluído.
 */
const props = defineProps<{
    proposalId: number;
    applicants: ApplicantFormData[];
    maritalStatuses: Option<number>[];
    bankAccountTypes: Option<number>[];
}>();

const emptyBankAccount = () => ({ bank_name: "", account_type: undefined as number | undefined, branch: "", number: "", notes: "" });

const emptyApplicant = () => ({
    existing: false,
    cpf: "",
    name: "",
    email: "",
    phone: "",
    birth_date: "",
    marital_status: null as number | null,
    profession: "",
    family_income: null as number | null,
    declared_income: null as number | null,
    declares_income_tax: false,
    income_tax_notes: "",
    by_power_of_attorney: false,
    bank_account: emptyBankAccount(),
});

const mode = ref<"create" | "edit" | null>(null);
const editingId = ref<number | null>(null);
const searching = ref(false);
const lookupMessage = ref("");

const form = useForm(emptyApplicant());

const yesNoFields: Array<{ name: "declares_income_tax" | "by_power_of_attorney"; label: string }> = [
    { name: "declares_income_tax", label: "Declara IR?" },
    { name: "by_power_of_attorney", label: "Por procuração?" },
];

function open(nextMode: "create" | "edit", data: ReturnType<typeof emptyApplicant>, id: number | null = null) {
    mode.value = nextMode;
    editingId.value = id;
    lookupMessage.value = "";
    form.clearErrors();
    Object.assign(form, data);
}

function create() {
    open("create", emptyApplicant());
}

function edit(applicant: ApplicantFormData) {
    open(
        "edit",
        {
            existing: false,
            cpf: maskCPF(applicant.cpf),
            name: applicant.name,
            email: applicant.email,
            phone: applicant.phone ? maskPhone(applicant.phone) : "",
            birth_date: applicant.birth_date ?? "",
            marital_status: applicant.marital_status,
            profession: applicant.profession ?? "",
            family_income: applicant.family_income,
            declared_income: applicant.declared_income,
            declares_income_tax: applicant.declares_income_tax,
            income_tax_notes: applicant.income_tax_notes ?? "",
            by_power_of_attorney: applicant.by_power_of_attorney,
            bank_account: { ...emptyBankAccount(), ...(applicant.bank_account ?? {}), notes: applicant.bank_account?.notes ?? "" },
        },
        applicant.id,
    );
}

function close() {
    mode.value = null;
    editingId.value = null;
    form.clearErrors();
}

async function lookup() {
    searching.value = true;
    lookupMessage.value = "";

    try {
        const { data } = await axios.get(route("tenant.documents.applicants.lookup"), { params: { cpf: form.cpf } });

        if (!data.applicant) {
            form.existing = false;
            lookupMessage.value = "CPF não cadastrado: preencha os dados do novo proponente.";
            return;
        }

        Object.assign(form, {
            ...data.applicant,
            cpf: maskCPF(data.applicant.cpf),
            phone: data.applicant.phone ? maskPhone(data.applicant.phone) : "",
            birth_date: data.applicant.birth_date ?? "",
            profession: data.applicant.profession ?? "",
            income_tax_notes: data.applicant.income_tax_notes ?? "",
            bank_account: { ...emptyBankAccount(), ...(data.applicant.bank_account ?? {}), notes: data.applicant.bank_account?.notes ?? "" },
        });

        lookupMessage.value = data.applicant.existing
            ? "Proponente já cadastrado: os dados dele serão reaproveitados."
            : "Contato encontrado no cadastro: complete os dados do proponente.";
    } catch (error: any) {
        lookupMessage.value = error?.response?.data?.message ?? "Não foi possível buscar o CPF.";
    } finally {
        searching.value = false;
    }
}

const pendingRemoval = ref<ApplicantFormData | null>(null);

function remove() {
    const applicant = pendingRemoval.value;

    if (applicant === null) {
        return;
    }

    router.delete(route("tenant.documents.proposals.applicants.destroy", [props.proposalId, applicant.id]), {
        preserveScroll: true,
        onSuccess: () => editingId.value === applicant.id && close(),
        onFinish: () => (pendingRemoval.value = null),
    });
}

function submit() {
    const options = { preserveScroll: true, onSuccess: close };

    if (mode.value === "create") {
        form.transform((data) => data).post(route("tenant.documents.proposals.applicants.store", props.proposalId), options);

        return;
    }

    if (editingId.value !== null) {
        form.transform(({ cpf, existing, ...data }) => data).put(
            route("tenant.documents.proposals.applicants.update", [props.proposalId, editingId.value]),
            options,
        );
    }
}
</script>

<template>
    <div class="space-y-6 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-lg font-semibold text-card-foreground">Proponente(s)</h3>
            <Button v-if="mode === null" type="button" variant="outline" @click="create">
                <UserPlus class="mr-1 h-4 w-4" /> Adicionar proponente
            </Button>
        </div>

        <form v-if="mode !== null" class="space-y-6" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:col-span-2">
                    <Field>
                        <FieldLabel for="applicant-edit-cpf">CPF{{ mode === "create" ? " *" : "" }}</FieldLabel>
                        <div v-if="mode === 'create'" class="flex gap-2">
                            <Input
                                id="applicant-edit-cpf"
                                :model-value="form.cpf"
                                placeholder="000.000.000-00"
                                :disabled="form.existing"
                                @input="(e: Event) => handleMask(e, maskCPF, (val) => (form.cpf = val))"
                            />
                            <Button type="button" variant="outline" title="Buscar CPF" :disabled="searching || form.existing" @click="lookup">
                                <Loader2 v-if="searching" class="h-4 w-4 animate-spin" />
                                <Search v-else class="h-4 w-4" />
                            </Button>
                        </div>
                        <Input v-else id="applicant-edit-cpf" :model-value="form.cpf" disabled />
                        <FieldDescription v-if="lookupMessage">{{ lookupMessage }}</FieldDescription>
                        <FieldError v-if="form.errors.cpf">{{ form.errors.cpf }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="applicant-edit-name">Nome *</FieldLabel>
                        <Input id="applicant-edit-name" v-model="form.name" :disabled="form.existing" />
                        <FieldError v-if="form.errors.name">{{ form.errors.name }}</FieldError>
                    </Field>

                    <template v-if="!form.existing">
                        <Field>
                            <FieldLabel for="applicant-edit-marital_status">Estado civil *</FieldLabel>
                            <Select
                                :model-value="form.marital_status ? String(form.marital_status) : ''"
                                @update:model-value="form.marital_status = Number($event)"
                            >
                                <SelectTrigger id="applicant-edit-marital_status">
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
                            <FieldLabel for="applicant-edit-profession">Profissão *</FieldLabel>
                            <Input id="applicant-edit-profession" v-model="form.profession" />
                            <FieldError v-if="form.errors.profession">{{ form.errors.profession }}</FieldError>
                        </Field>
                        <Field>
                            <FieldLabel for="applicant-edit-birth_date">Data de nascimento</FieldLabel>
                            <Input id="applicant-edit-birth_date" v-model="form.birth_date" type="date" />
                            <FieldError v-if="form.errors.birth_date">{{ form.errors.birth_date }}</FieldError>
                        </Field>
                        <Field>
                            <FieldLabel for="applicant-edit-phone">Telefone *</FieldLabel>
                            <Input
                                id="applicant-edit-phone"
                                :model-value="form.phone"
                                @input="(e: Event) => handleMask(e, maskPhone, (val) => (form.phone = val))"
                            />
                            <FieldError v-if="form.errors.phone">{{ form.errors.phone }}</FieldError>
                        </Field>
                        <Field>
                            <FieldLabel for="applicant-edit-declared_income">Renda declarada *</FieldLabel>
                            <MoneyInput id="applicant-edit-declared_income" v-model="form.declared_income" />
                            <FieldError v-if="form.errors.declared_income">{{ form.errors.declared_income }}</FieldError>
                        </Field>
                        <Field>
                            <FieldLabel for="applicant-edit-email">E-mail *</FieldLabel>
                            <Input id="applicant-edit-email" v-model="form.email" type="email" />
                            <FieldError v-if="form.errors.email">{{ form.errors.email }}</FieldError>
                        </Field>
                        <Field>
                            <FieldLabel for="applicant-edit-family_income">Renda familiar</FieldLabel>
                            <MoneyInput id="applicant-edit-family_income" v-model="form.family_income" nullable />
                            <FieldError v-if="form.errors.family_income">{{ form.errors.family_income }}</FieldError>
                        </Field>
                        <Field v-for="field in yesNoFields" :key="field.name">
                            <FieldLabel :for="`applicant-edit-${field.name}`">{{ field.label }}</FieldLabel>
                            <Select
                                :model-value="form[field.name] ? '1' : '0'"
                                @update:model-value="form[field.name] = $event === '1'"
                            >
                                <SelectTrigger :id="`applicant-edit-${field.name}`">
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
                            <FieldLabel for="applicant-edit-income_tax_notes">Observações sobre o imposto de renda</FieldLabel>
                            <Textarea id="applicant-edit-income_tax_notes" v-model="form.income_tax_notes" />
                        </Field>
                    </template>
                </div>

                <div v-if="!form.existing" class="space-y-4">
                    <p class="text-sm font-medium text-muted-foreground">Dados bancários (opcional)</p>
                    <BankAccountFields
                        :account="form.bank_account"
                        :account-types="bankAccountTypes"
                        :errors="form.errors"
                        error-prefix="bank_account"
                        id-prefix="applicant-edit"
                        stacked
                    />
                    <Field>
                        <FieldLabel for="applicant-edit-bank_notes">Observações sobre os dados bancários</FieldLabel>
                        <Textarea id="applicant-edit-bank_notes" v-model="form.bank_account.notes" />
                    </Field>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button type="submit" :loading="form.processing" :disabled="form.processing">
                    <template v-if="mode === 'create'"><UserPlus class="mr-1 h-4 w-4" /> Adicionar proponente</template>
                    <template v-else><UserPen class="mr-1 h-4 w-4" /> Atualizar proponente</template>
                </Button>
                <Button type="button" variant="outline" :disabled="form.processing" @click="close">Cancelar</Button>
            </div>
        </form>

        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nome do proponente</TableHead>
                        <TableHead>CPF</TableHead>
                        <TableHead>Telefone</TableHead>
                        <TableHead>E-mail</TableHead>
                        <TableHead class="text-right">Ações</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="applicant in applicants" :key="applicant.id" :class="{ 'bg-muted/50': editingId === applicant.id }">
                        <TableCell>{{ applicant.name }}</TableCell>
                        <TableCell>{{ maskCPF(applicant.cpf) }}</TableCell>
                        <TableCell>{{ applicant.phone ? maskPhone(applicant.phone) : "—" }}</TableCell>
                        <TableCell>{{ applicant.email }}</TableCell>
                        <TableCell class="whitespace-nowrap text-right">
                            <Button type="button" variant="ghost" size="sm" title="Editar proponente" @click="edit(applicant)">
                                <Pencil class="h-4 w-4" />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="text-red-600"
                                title="Remover proponente"
                                @click="pendingRemoval = applicant"
                            >
                                <Trash class="h-4 w-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="!applicants.length">
                        <TableCell colspan="5" class="h-16 text-center">Nenhum proponente.</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <ConfirmDeleteDialog
            :open="pendingRemoval !== null"
            title="Remover proponente?"
            :description="`${pendingRemoval?.name ?? 'O proponente'} será desvinculado desta proposta. O cadastro dele e os documentos já enviados são mantidos.`"
            @update:open="(value) => !value && (pendingRemoval = null)"
            @confirm="remove"
        />
    </div>
</template>
