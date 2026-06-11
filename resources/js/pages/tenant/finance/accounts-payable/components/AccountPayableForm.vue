<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import FieldError from "@/components/ui/field/FieldError.vue";
import { maskCurrency, parseCurrencyToCents } from "@/lib/masks";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Calendar } from "lucide-vue-next";

const props = withDefaults(
    defineProps<{
        form: ReturnType<typeof useForm>;
        financialCategories: any[];
        financialSubcategories: any[];
        costs: any[];
        contacts: any[];
        paymentConditions: Record<string, string>;
        bankAccounts: any[];
        accountPayable?: any;
        submitText?: string;
    }>(),
    {
        submitText: "Salvar Lançamento",
    }
);

const emit = defineEmits(["submit"]);

// We check if we are in Edit mode
const isEdit = computed(() => !!props.accountPayable);

// Installments list for individual editing (used in Edit mode)
const localInstallments = ref<any[]>(
    props.accountPayable?.installments?.map((inst: any) => ({
        installment_id: inst.id,
        installment_number: inst.installment_number,
        value: maskCurrency(String(inst.value)),
        due_date: inst.due_date ? inst.due_date.split("T")[0] : "",
    })) || []
);

// Watch category to reset subcategory if category changes
watch(
    () => props.form.financial_category_id,
    () => {
        // Only reset if category is changed manually and not during initialization
        if (props.form.wasSuccessful === false) {
            props.form.financial_subcategory_id = "";
        }
    }
);

// Watch bank account to sync bank_account_out
watch(
    () => props.form.bank_account_id,
    (val) => {
        props.form.bank_account_out = val ? Number(val) : "";
    }
);

// Filter subcategories based on selected category
const filteredSubcategories = computed(() => {
    if (!props.financialSubcategories) return [];
    return props.financialSubcategories.filter((sub: any) => {
        if (!sub) return false;
        if (typeof sub === "string") return true;
        if (props.form.financial_category_id) {
            return Number(sub.financial_category_id) === Number(props.form.financial_category_id);
        }
        return true;
    });
});

// Watch payment condition and total value to calculate installments value
watch(
    [() => props.form.payment_condition, () => props.form.total],
    ([condition, totalVal]) => {
        if (!condition || isEdit.value) return;

        const totalCents = parseCurrencyToCents(totalVal as string);
        let installmentsCount = 1;

        if (condition !== "a-vista") {
            installmentsCount = parseInt(condition) || 1;
        }

        props.form.total_installments = installmentsCount;

        if (totalCents > 0) {
            const installmentValueCents = Math.round(totalCents / installmentsCount);
            props.form.value = maskCurrency(String(installmentValueCents));
        } else {
            props.form.value = "";
        }
    }
);

// Watch first installment value to update total if condition is à-vista
watch(
    () => props.form.value,
    (val) => {
        if (props.form.payment_condition === "a-vista" && !isEdit.value) {
            props.form.total = val;
        }
    }
);

function onSubmit() {
    // If editing individual installments and total hasn't changed, pass them in payload
    if (isEdit.value && localInstallments.value.length > 0) {
        props.form.installments = localInstallments.value.map((inst) => ({
            installment_id: inst.installment_id,
            value: parseCurrencyToCents(inst.value),
            due_date: inst.due_date,
        }));
    }
    emit("submit");
}
</script>

<template>
    <form
        @submit.prevent="onSubmit"
        class="space-y-8 rounded-xl border border-border bg-card p-6 shadow-sm sm:p-8"
    >
        <!-- Section 1: Informações Gerais -->
        <div class="space-y-6">
            <h3 class="text-lg font-semibold text-card-foreground border-b border-border/60 pb-2">
                Dados do Lançamento
            </h3>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Descrição -->
                <Field class="md:col-span-2">
                    <FieldLabel for="description">Descrição *</FieldLabel>
                    <Input
                        id="description"
                        v-model="props.form.description"
                        placeholder="Ex: Compra de Licenças de Software, Aluguel do Escritório"
                        required
                    />
                    <FieldError v-if="props.form.errors.description">
                        {{ props.form.errors.description }}
                    </FieldError>
                </Field>

                <!-- Fornecedor -->
                <Field>
                    <FieldLabel for="financial_contact_id">Fornecedor *</FieldLabel>
                    <Select
                        :model-value="props.form.financial_contact_id ? String(props.form.financial_contact_id) : ''"
                        @update:model-value="props.form.financial_contact_id = $event"
                    >
                        <SelectTrigger id="financial_contact_id">
                            <SelectValue placeholder="Selecione o fornecedor..." />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectGroup>
                                <SelectItem
                                    v-for="contact in props.contacts"
                                    :key="contact.id"
                                    :value="String(contact.id)"
                                >
                                    {{ contact.name_corporatereason }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="props.form.errors.financial_contact_id">
                        {{ props.form.errors.financial_contact_id }}
                    </FieldError>
                </Field>

                <!-- Conta Bancária de Origem -->
                <Field>
                    <FieldLabel for="bank_account_id">Conta Bancária *</FieldLabel>
                    <Select
                        :model-value="props.form.bank_account_id ? String(props.form.bank_account_id) : ''"
                        @update:model-value="props.form.bank_account_id = $event"
                    >
                        <SelectTrigger id="bank_account_id">
                            <SelectValue placeholder="Selecione a conta de origem..." />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectGroup>
                                <SelectItem
                                    v-for="acc in props.bankAccounts"
                                    :key="acc.id"
                                    :value="String(acc.id)"
                                >
                                    {{ acc.name }} (R$ {{ acc.current_balance ? (acc.current_balance/100).toLocaleString('pt-BR', {minimumFractionDigits: 2}) : '0,00' }})
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="props.form.errors.bank_account_id">
                        {{ props.form.errors.bank_account_id }}
                    </FieldError>
                </Field>

                <!-- Categoria Financeira -->
                <Field>
                    <FieldLabel for="financial_category_id">Categoria Financeira *</FieldLabel>
                    <Select
                        :model-value="props.form.financial_category_id ? String(props.form.financial_category_id) : ''"
                        @update:model-value="props.form.financial_category_id = $event"
                    >
                        <SelectTrigger id="financial_category_id">
                            <SelectValue placeholder="Selecione a categoria..." />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectGroup>
                                <SelectItem
                                    v-for="cat in props.financialCategories"
                                    :key="cat.id"
                                    :value="String(cat.id)"
                                >
                                    {{ cat.name }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="props.form.errors.financial_category_id">
                        {{ props.form.errors.financial_category_id }}
                    </FieldError>
                </Field>

                <!-- Subcategoria Financeira -->
                <Field>
                    <FieldLabel for="financial_subcategory_id">Subcategoria Financeira</FieldLabel>
                    <Select
                        :model-value="props.form.financial_subcategory_id ? String(props.form.financial_subcategory_id) : ''"
                        @update:model-value="props.form.financial_subcategory_id = $event"
                        :disabled="!props.form.financial_category_id"
                    >
                        <SelectTrigger id="financial_subcategory_id">
                            <SelectValue placeholder="Selecione a subcategoria..." />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectGroup>
                                <SelectItem
                                    v-for="sub in filteredSubcategories"
                                    :key="sub.id || sub"
                                    :value="String(sub.id || sub)"
                                >
                                    {{ sub.name || sub }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="props.form.errors.financial_subcategory_id">
                        {{ props.form.errors.financial_subcategory_id }}
                    </FieldError>
                </Field>

                <!-- Centro de Custo -->
                <Field>
                    <FieldLabel for="cost_id">Centro de Custo</FieldLabel>
                    <Select
                        :model-value="props.form.cost_id ? String(props.form.cost_id) : ''"
                        @update:model-value="props.form.cost_id = $event"
                    >
                        <SelectTrigger id="cost_id">
                            <SelectValue placeholder="Selecione o centro de custo..." />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectGroup>
                                <SelectItem
                                    v-for="cost in props.costs"
                                    :key="cost.id"
                                    :value="String(cost.id)"
                                >
                                    {{ cost.type }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="props.form.errors.cost_id">
                        {{ props.form.errors.cost_id }}
                    </FieldError>
                </Field>
            </div>
        </div>

        <!-- Section 2: Valores e Parcelamento -->
        <div class="space-y-6">
            <h3 class="text-lg font-semibold text-card-foreground border-b border-border/60 pb-2">
                Valores e Condições de Pagamento
            </h3>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Condição de Pagamento -->
                <Field>
                    <FieldLabel for="payment_condition">Condição de Pagamento *</FieldLabel>
                    <Select
                        :model-value="props.form.payment_condition ? String(props.form.payment_condition) : ''"
                        @update:model-value="props.form.payment_condition = $event"
                    >
                        <SelectTrigger id="payment_condition">
                            <SelectValue placeholder="Selecione a condição..." />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectGroup>
                                <SelectItem value="a-vista">À Vista</SelectItem>
                                <SelectItem
                                    v-for="(label, key) in props.paymentConditions"
                                    :key="key"
                                    :value="String(key)"
                                >
                                    {{ label }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="props.form.errors.payment_condition">
                        {{ props.form.errors.payment_condition }}
                    </FieldError>
                </Field>

                <!-- Forma de Pagamento -->
                <Field>
                    <FieldLabel for="payment_method">Forma de Pagamento *</FieldLabel>
                    <Select
                        :model-value="props.form.payment_method ? String(props.form.payment_method) : ''"
                        @update:model-value="props.form.payment_method = $event"
                    >
                        <SelectTrigger id="payment_method">
                            <SelectValue placeholder="Selecione a forma..." />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectGroup>
                                <SelectItem value="money">Dinheiro</SelectItem>
                                <SelectItem value="pix">Pix</SelectItem>
                                <SelectItem value="ticket">Boleto</SelectItem>
                                <SelectItem value="credit_card">Cartão de Crédito</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="props.form.errors.payment_method">
                        {{ props.form.errors.payment_method }}
                    </FieldError>
                </Field>

                <!-- Valor Total -->
                <Field>
                    <FieldLabel for="total">Valor Total *</FieldLabel>
                    <Input
                        id="total"
                        :model-value="props.form.total"
                        @input="
                            (e: Event) => {
                                const val = maskCurrency(
                                    (e.target as HTMLInputElement).value,
                                );
                                props.form.total = val;
                                (e.target as HTMLInputElement).value = val;
                            }
                        "
                        placeholder="R$ 0,00"
                        required
                        :disabled="props.form.payment_condition === 'a-vista' && !isEdit"
                    />
                    <FieldError v-if="props.form.errors.total">
                        {{ props.form.errors.total }}
                    </FieldError>
                </Field>

                <!-- Valor da Parcela -->
                <Field>
                    <FieldLabel for="value">Valor da Parcela *</FieldLabel>
                    <Input
                        id="value"
                        :model-value="props.form.value"
                        @input="
                            (e: Event) => {
                                const val = maskCurrency(
                                    (e.target as HTMLInputElement).value,
                                );
                                props.form.value = val;
                                (e.target as HTMLInputElement).value = val;
                            }
                        "
                        placeholder="R$ 0,00"
                        required
                    />
                    <FieldError v-if="props.form.errors.value">
                        {{ props.form.errors.value }}
                    </FieldError>
                </Field>

                <!-- Data de Vencimento -->
                <Field>
                    <FieldLabel for="due_date">Data de Vencimento *</FieldLabel>
                    <div class="relative">
                        <Input
                            id="due_date"
                            type="date"
                            v-model="props.form.due_date"
                            required
                            class="pr-10"
                        />
                    </div>
                    <FieldError v-if="props.form.errors.due_date">
                        {{ props.form.errors.due_date }}
                    </FieldError>
                </Field>

                <!-- Status -->
                <Field>
                    <FieldLabel for="status">Situação Inicial *</FieldLabel>
                    <Select
                        :model-value="props.form.status ? String(props.form.status) : ''"
                        @update:model-value="props.form.status = $event"
                    >
                        <SelectTrigger id="status">
                            <SelectValue placeholder="Selecione o status..." />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectGroup>
                                <SelectItem value="open">Em Aberto</SelectItem>
                                <SelectItem value="paid">Pago</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="props.form.errors.status">
                        {{ props.form.errors.status }}
                    </FieldError>
                </Field>

                <!-- Observações -->
                <Field class="md:col-span-2">
                    <FieldLabel for="observations">Observações</FieldLabel>
                    <Textarea
                        id="observations"
                        v-model="props.form.observations"
                        placeholder="Observações ou detalhes adicionais sobre o lançamento..."
                        rows="3"
                    />
                    <FieldError v-if="props.form.errors.observations">
                        {{ props.form.errors.observations }}
                    </FieldError>
                </Field>

                <!-- Comprovante (Recibo/Anexo) -->
                <Field class="md:col-span-2">
                    <FieldLabel for="receipt">Link do Comprovante / Anexo</FieldLabel>
                    <Input
                        id="receipt"
                        v-model="props.form.receipt"
                        placeholder="Ex: Link do Google Drive, Dropbox ou referência do comprovante"
                    />
                    <FieldError v-if="props.form.errors.receipt">
                        {{ props.form.errors.receipt }}
                    </FieldError>
                </Field>
            </div>
        </div>

        <!-- Section 3: Edição das parcelas individuais (Apenas em Edição) -->
        <div
            v-if="isEdit && localInstallments.length > 0"
            class="space-y-6 border-t border-border pt-6"
        >
            <div class="flex flex-col gap-1">
                <h3 class="text-lg font-semibold text-card-foreground">
                    Detalhamento das Parcelas
                </h3>
                <p class="text-xs text-muted-foreground">
                    Modifique datas de vencimento ou valores individuais se necessário. (Caso altere o valor total acima, estas parcelas serão recalculadas ao salvar).
                </p>
            </div>

            <div class="rounded-xl border border-border overflow-hidden">
                <div class="bg-muted/30 px-4 py-3 border-b border-border font-bold grid grid-cols-3 text-xs text-muted-foreground tracking-wider uppercase">
                    <div>Parcela</div>
                    <div>Valor da Parcela</div>
                    <div>Vencimento</div>
                </div>

                <div class="divide-y divide-border">
                    <div
                        v-for="(inst, idx) in localInstallments"
                        :key="idx"
                        class="p-4 grid grid-cols-3 gap-4 items-center bg-background"
                    >
                        <div class="font-semibold text-sm">
                            Parcela {{ inst.installment_number }} / {{ localInstallments.length }}
                        </div>

                        <div>
                            <Input
                                v-model="inst.value"
                                @input="
                                    (e: Event) => {
                                        const val = maskCurrency(
                                            (e.target as HTMLInputElement).value,
                                        );
                                        inst.value = val;
                                        (e.target as HTMLInputElement).value = val;
                                    }
                                "
                                placeholder="R$ 0,00"
                                class="h-9 text-sm"
                            />
                        </div>

                        <div>
                            <Input
                                type="date"
                                v-model="inst.due_date"
                                class="h-9 text-sm"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end border-t border-border pt-6">
            <Button
                type="submit"
                class="text-md w-full px-10 font-bold md:w-auto cursor-pointer"
                :loading="props.form.processing"
                :disabled="props.form.processing"
            >
                {{ submitText }}
            </Button>
        </div>
    </form>
</template>

<style scoped></style>
