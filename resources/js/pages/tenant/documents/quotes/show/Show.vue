<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { ChevronLeft, FilePlus, Mail, Pencil, Printer } from "lucide-vue-next";
import { route } from "ziggy-js";
import { ref } from "vue";
import { maskCPF } from "@/lib/masks";
import { usePermission } from "@/composables/usePermission";
import FeeResultTable from "@/pages/tenant/documents/fee-calculator/components/FeeResultTable.vue";
import type { QuoteDetail } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    quote: QuoteDetail;
}>();

const { permissions } = usePermission();
const showConvertDialog = ref(false);
const converting = ref(false);
const sending = ref(false);

function sendEmail() {
    sending.value = true;
    router.post(route("tenant.documents.quotes.email", props.quote.id), {}, { preserveScroll: true, onFinish: () => (sending.value = false) });
}

function convert() {
    converting.value = true;
    router.post(route("tenant.documents.quotes.convert", props.quote.id), {}, {
        onFinish: () => {
            converting.value = false;
            showConvertDialog.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`Orçamento Nº ${quote.number}`" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">Orçamento Nº {{ quote.number }}</h2>
            <p class="text-sm text-muted-foreground">{{ quote.status.label }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <Button variant="outline" as-child>
                <a :href="route('tenant.documents.quotes.pdf', quote.id)" target="_blank"><Printer class="mr-1 h-4 w-4" /> Imprimir</a>
            </Button>
            <Button v-if="permissions.includes('documents.quotes.edit')" variant="outline" :disabled="sending" @click="sendEmail">
                <Mail class="mr-1 h-4 w-4" /> Enviar por e-mail
            </Button>
            <template v-if="quote.status.value === 'open' && permissions.includes('documents.quotes.edit')">
                <Button variant="outline" as-child>
                    <Link :href="route('tenant.documents.quotes.edit', quote.id)"><Pencil class="mr-1 h-4 w-4" /> Editar</Link>
                </Button>
                <Button @click="showConvertDialog = true"><FilePlus class="mr-1 h-4 w-4" /> Gerar proposta</Button>
            </template>
            <Button variant="outline" as-child>
                <Link :href="route('tenant.documents.quotes.list')"><ChevronLeft class="mr-1 h-4 w-4" /> Voltar</Link>
            </Button>
        </div>
    </div>

    <div class="mx-auto mb-20 max-w-6xl space-y-6">
        <div
            v-if="quote.status.value === 'converted'"
            class="rounded-md border border-green-300 bg-green-50 p-4 text-sm text-green-900 dark:border-green-700 dark:bg-green-950 dark:text-green-100"
        >
            Atenção! já foi gerada uma proposta para este orçamento.
            <Link v-if="quote.proposal_id" :href="route('tenant.documents.proposals.edit', quote.proposal_id)" class="ml-1 font-semibold underline">Abrir proposta</Link>
        </div>
        <div
            v-if="quote.status.value === 'expired'"
            class="rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-900 dark:border-red-700 dark:bg-red-950 dark:text-red-100"
        >
            Atenção: Orçamento com data de validade expirado, será necessário gerar um novo!
        </div>

        <section class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-card-foreground">Cliente</h3>
            <dl class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                <div><dt class="text-muted-foreground">Nome</dt><dd>{{ quote.name }}</dd></div>
                <div><dt class="text-muted-foreground">CPF</dt><dd>{{ maskCPF(quote.cpf) }}</dd></div>
                <div><dt class="text-muted-foreground">E-mail</dt><dd>{{ quote.email }}</dd></div>
                <div><dt class="text-muted-foreground">Telefone</dt><dd>{{ quote.phone }}</dd></div>
                <div><dt class="text-muted-foreground">Profissão</dt><dd>{{ quote.profession }}</dd></div>
                <div><dt class="text-muted-foreground">Estado civil</dt><dd>{{ quote.marital_status_label }}</dd></div>
                <div><dt class="text-muted-foreground">Banco</dt><dd>{{ quote.bank }}</dd></div>
                <div>
                    <dt class="text-muted-foreground">Validade</dt>
                    <dd>{{ quote.valid_until ? new Date(`${quote.valid_until}T00:00:00`).toLocaleDateString("pt-BR") : "—" }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-card-foreground">
                {{ quote.calculation.type }} ({{ quote.calculation.state }} - {{ quote.calculation.municipality_name }})
            </h3>
            <FeeResultTable :breakdown="quote.breakdown" />
        </section>
    </div>

    <AlertDialog :open="showConvertDialog" @update:open="showConvertDialog = $event">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Gerar proposta a partir deste orçamento?</AlertDialogTitle>
                <AlertDialogDescription>
                    Será criada uma proposta com o cliente, as linhas de custo e os serviços do orçamento. O cliente receberá um
                    e-mail de acesso.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="showConvertDialog = false">Cancelar</AlertDialogCancel>
                <AlertDialogAction :disabled="converting" @click="convert">Gerar proposta</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
