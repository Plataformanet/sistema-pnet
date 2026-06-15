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
import { route } from "ziggy-js";

import ComboboxRemote from "@/components/ui/combobox/ComboboxRemote.vue";

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
        initialContact?: any;
    }>(),
    {
        submitText: "Salvar Lançamento",
        initialContact: null,
    },
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
        status: inst.status,
    })) || [],
);

// Watch category to reset subcategory if category changes
watch(
    () => props.form.financial_category_id,
    () => {
        // Only reset if category is changed manually and not during initialization
        if (props.form.wasSuccessful === false) {
            props.form.financial_subcategory_id = "";
        }
    },
);

// Watch bank account to sync bank_account_out
watch(
    () => props.form.bank_account_id,
    (val) => {
        props.form.bank_account_out = val ? Number(val) : "";
    },
);

// Filter subcategories based on selected category
const filteredSubcategories = computed(() => {
    if (!props.financialSubcategories) return [];
    return props.financialSubcategories.filter((sub: any) => {
        if (!sub) return false;
        if (typeof sub === "string") return true;
        if (props.form.financial_category_id) {
            return (
                Number(sub.financial_category_id) ===
                Number(props.form.financial_category_id)
            );
        }
        return true;
    });
});

function calculateInstallmentDueDate(
    startDateStr: string,
    index: number,
): string {
    if (!startDateStr) return "";
    const date = new Date(startDateStr + "T00:00:00");
    if (isNaN(date.getTime())) return "";

    const dayOriginal = date.getDate();
    const year = date.getFullYear();
    const month = date.getMonth(); // 0-indexed

    // Calculate adjusted month and year
    const monthCurrent = month + index;
    const yearCurrent = year + Math.floor(monthCurrent / 12);
    const monthAdjust = monthCurrent % 12;

    // Create a temporary date on the 1st of the target month to find the number of days in that month
    const tempDate = new Date(yearCurrent, monthAdjust + 1, 0); // last day of month
    const lastDayOfMonth = tempDate.getDate();
    const dayAdjust = Math.min(dayOriginal, lastDayOfMonth);

    // Format as YYYY-MM-DD
    const finalYear = String(yearCurrent);
    const finalMonth = String(monthAdjust + 1).padStart(2, "0");
    const finalDay = String(dayAdjust).padStart(2, "0");

    return `${finalYear}-${finalMonth}-${finalDay}`;
}

// Watch payment condition, total value and due date to calculate installments value and populate localInstallments
watch(
    [
        () => props.form.payment_condition,
        () => props.form.total,
        () => props.form.due_date,
    ],
    ([condition, totalVal, dueDateVal]) => {
        if (isEdit.value) return;

        const totalCents = parseCurrencyToCents(totalVal as string);
        let installmentsCount = 1;

        if (condition && condition !== "a-vista") {
            installmentsCount = parseInt(condition) || 1;
        }

        props.form.total_installments = installmentsCount;

        if (totalCents > 0) {
            const installmentValueCents = Math.round(
                totalCents / installmentsCount,
            );
            props.form.value = maskCurrency(String(installmentValueCents));

            if (installmentsCount > 1) {
                const arr = [];
                for (let i = 0; i < installmentsCount; i++) {
                    let valCents = installmentValueCents;
                    if (i === installmentsCount - 1) {
                        valCents =
                            totalCents -
                            installmentValueCents * (installmentsCount - 1);
                    }

                    arr.push({
                        installment_id: null,
                        installment_number: i + 1,
                        value: maskCurrency(String(valCents)),
                        due_date: dueDateVal
                            ? calculateInstallmentDueDate(
                                  dueDateVal as string,
                                  i,
                              )
                            : "",
                        status: "open",
                    });
                }
                localInstallments.value = arr;
            } else {
                localInstallments.value = [];
            }
        } else {
            props.form.value = "";
            localInstallments.value = [];
        }
    },
);

// Watch total value to update first installment value if not parcelled
watch(
    () => props.form.total,
    (val) => {
        if (localInstallments.value.length <= 1) {
            props.form.value = val;
        }
    },
);

function onInstallmentValueChange(
    editedIndex: number,
    newValueStr: string,
    inputElement?: HTMLInputElement,
) {
    const totalCents = parseCurrencyToCents(props.form.total as string);
    if (!(totalCents > 0)) return;

    const insts = localInstallments.value;
    if (insts.length <= 1) return;

    let editedValueCents = parseCurrencyToCents(newValueStr);

    let paidSumCents = 0;
    insts.forEach((inst, index) => {
        if (inst.status === "paid" && index !== editedIndex) {
            paidSumCents += parseCurrencyToCents(inst.value);
        }
    });

    // Enforce that the installment value cannot be greater than the total minus any paid installments
    const maxValCents = totalCents - paidSumCents;
    if (editedValueCents > maxValCents) {
        editedValueCents = maxValCents;
        const cappedFormatted = maskCurrency(String(editedValueCents));
        insts[editedIndex].value = cappedFormatted;
        if (inputElement) {
            inputElement.value = cappedFormatted;
        }
    }

    const targets = insts.filter((inst, index) => {
        return inst.status !== "paid" && index !== editedIndex;
    });

    if (targets.length === 0) return;

    const remainingCents = totalCents - paidSumCents - editedValueCents;

    const targetCount = targets.length;
    const baseValueCents = Math.trunc(remainingCents / targetCount);

    targets.forEach((inst, i) => {
        let valCents = baseValueCents;
        if (i === targetCount - 1) {
            valCents = remainingCents - baseValueCents * (targetCount - 1);
        }

        if (valCents < 0) {
            valCents = 0;
        }

        inst.value = maskCurrency(String(valCents));
    });
}

function onSubmit() {
    // Pass custom installments to form if they exist
    if (localInstallments.value.length > 1) {
        props.form.installments = localInstallments.value.map((inst) => ({
            installment_id: inst.installment_id || undefined,
            value: parseCurrencyToCents(inst.value),
            due_date: inst.due_date,
        }));
    } else {
        props.form.installments = [];
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
            <h3
                class="border-b border-border/60 pb-2 text-lg font-semibold text-card-foreground"
            >
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
                    <FieldLabel for="financial_contact_id"
                        >Fornecedor *</FieldLabel
                    >
                    <ComboboxRemote
                        id="financial_contact_id"
                        v-model="props.form.financial_contact_id"
                        :url="
                            route(
                                'tenant.finance.accounts-payable.search-contact',
                            )
                        "
                        :query-params="{ type: 'supplier' }"
                        placeholder="Selecione o fornecedor..."
                        search-placeholder="Pesquisar fornecedor..."
                        :initial-item="props.initialContact"
                    />
                    <FieldError v-if="props.form.errors.financial_contact_id">
                        {{ props.form.errors.financial_contact_id }}
                    </FieldError>
                </Field>

                <!-- Conta Bancária de Origem -->
                <Field>
                    <FieldLabel for="bank_account_id"
                        >Conta Bancária *</FieldLabel
                    >
                    <Select
                        :model-value="
                            props.form.bank_account_id
                                ? String(props.form.bank_account_id)
                                : ''
                        "
                        @update:model-value="
                            props.form.bank_account_id = $event
                        "
                    >
                        <SelectTrigger id="bank_account_id">
                            <SelectValue
                                placeholder="Selecione a conta de origem..."
                            />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectGroup>
                                <SelectItem
                                    v-for="acc in props.bankAccounts"
                                    :key="acc.id"
                                    :value="String(acc.id)"
                                >
                                    {{ acc.name }} (R$
                                    {{
                                        acc.current_balance
                                            ? (
                                                  acc.current_balance / 100
                                              ).toLocaleString("pt-BR", {
                                                  minimumFractionDigits: 2,
                                              })
                                            : "0,00"
                                    }})
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
                    <FieldLabel for="financial_category_id"
                        >Categoria Financeira *</FieldLabel
                    >
                    <Select
                        :model-value="
                            props.form.financial_category_id
                                ? String(props.form.financial_category_id)
                                : ''
                        "
                        @update:model-value="
                            props.form.financial_category_id = $event
                        "
                    >
                        <SelectTrigger id="financial_category_id">
                            <SelectValue
                                placeholder="Selecione a categoria..."
                            />
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
                    <FieldLabel for="financial_subcategory_id"
                        >Subcategoria Financeira</FieldLabel
                    >
                    <Select
                        :model-value="
                            props.form.financial_subcategory_id
                                ? String(props.form.financial_subcategory_id)
                                : ''
                        "
                        @update:model-value="
                            props.form.financial_subcategory_id = $event
                        "
                        :disabled="!props.form.financial_category_id"
                    >
                        <SelectTrigger id="financial_subcategory_id">
                            <SelectValue
                                placeholder="Selecione a subcategoria..."
                            />
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
                    <FieldError
                        v-if="props.form.errors.financial_subcategory_id"
                    >
                        {{ props.form.errors.financial_subcategory_id }}
                    </FieldError>
                </Field>

                <!-- Centro de Custo -->
                <Field>
                    <FieldLabel for="cost_id">Centro de Custo</FieldLabel>
                    <Select
                        :model-value="
                            props.form.cost_id ? String(props.form.cost_id) : ''
                        "
                        @update:model-value="props.form.cost_id = $event"
                    >
                        <SelectTrigger id="cost_id">
                            <SelectValue
                                placeholder="Selecione o centro de custo..."
                            />
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
            <h3
                class="border-b border-border/60 pb-2 text-lg font-semibold text-card-foreground"
            >
                Valores e Condições de Pagamento
            </h3>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Condição de Pagamento -->
                <Field>
                    <FieldLabel for="payment_condition"
                        >Condição de Pagamento *</FieldLabel
                    >
                    <Select
                        :model-value="
                            props.form.payment_condition
                                ? String(props.form.payment_condition)
                                : ''
                        "
                        @update:model-value="
                            props.form.payment_condition = $event
                        "
                    >
                        <SelectTrigger id="payment_condition">
                            <SelectValue
                                placeholder="Selecione a condição..."
                            />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectGroup>
                                <SelectItem value="a-vista">À Vista</SelectItem>
                                <SelectItem
                                    v-for="(
                                        label, key
                                    ) in props.paymentConditions"
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
                    <FieldLabel for="payment_method"
                        >Forma de Pagamento *</FieldLabel
                    >
                    <Select
                        :model-value="
                            props.form.payment_method
                                ? String(props.form.payment_method)
                                : ''
                        "
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
                                <SelectItem value="credit_card"
                                    >Cartão de Crédito</SelectItem
                                >
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
                    <input
                        id="total"
                        :value="props.form.total"
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
                        class="flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30"
                    />
                    <FieldError v-if="props.form.errors.total">
                        {{ props.form.errors.total }}
                    </FieldError>
                </Field>

                <!-- Valor da Parcela -->
                <Field>
                    <FieldLabel for="value">Valor da Parcela *</FieldLabel>
                    <input
                        id="value"
                        :value="props.form.value"
                        placeholder="R$ 0,00"
                        required
                        disabled
                        class="flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30"
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
                        :model-value="
                            props.form.status ? String(props.form.status) : ''
                        "
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
                    <FieldLabel for="receipt"
                        >Link do Comprovante / Anexo</FieldLabel
                    >
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

        <!-- Section 3: Edição das parcelas individuais (Apenas em Edição e se for parcelado) -->
        <div
            v-if="localInstallments.length > 1"
            class="space-y-6 border-t border-border pt-6"
        >
            <h3 class="text-xl font-bold text-card-foreground">Parcelas</h3>

            <div class="space-y-6">
                <div
                    v-for="(inst, idx) in localInstallments"
                    :key="idx"
                    class="relative grid grid-cols-1 gap-6 rounded-xl border border-border/60 bg-muted/10 p-5 sm:grid-cols-2"
                >
                    <!-- Badges showing installment status -->
                    <div
                        class="absolute -top-3 left-4 flex items-center gap-1.5 rounded border border-border bg-background px-2 py-0.5 text-xs font-semibold text-muted-foreground"
                    >
                        <span
                            >Parcela {{ inst.installment_number }} de
                            {{ localInstallments.length }}</span
                        >
                        <span
                            v-if="inst.status === 'paid'"
                            class="py-0.2 rounded border border-emerald-200 bg-emerald-50 px-1 text-[10px] font-bold tracking-wider text-emerald-600 uppercase"
                            >(Paga)</span
                        >
                    </div>

                    <!-- Vencimento -->
                    <Field>
                        <FieldLabel
                            >Vencimento
                            <span class="text-destructive">*</span>
                            :</FieldLabel
                        >
                        <Input
                            type="date"
                            v-model="inst.due_date"
                            :disabled="inst.status === 'paid'"
                            required
                        />
                    </Field>

                    <!-- Valor -->
                    <Field>
                        <FieldLabel
                            >Valor
                            <span class="text-destructive">*</span>
                            :</FieldLabel
                        >
                        <input
                            :value="inst.value"
                            @input="
                                (e: Event) => {
                                    const val = maskCurrency(
                                        (e.target as HTMLInputElement).value,
                                    );
                                    inst.value = val;
                                    (e.target as HTMLInputElement).value = val;
                                    onInstallmentValueChange(
                                        idx,
                                        val,
                                        e.target as HTMLInputElement,
                                    );
                                }
                            "
                            placeholder="R$ 0,00"
                            class="flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30"
                            :disabled="inst.status === 'paid'"
                            required
                        />
                    </Field>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end border-t border-border pt-6">
            <Button
                type="submit"
                class="text-md w-full cursor-pointer px-10 font-bold md:w-auto"
                :loading="props.form.processing"
                :disabled="props.form.processing"
            >
                {{ submitText }}
            </Button>
        </div>
    </form>
</template>

<style scoped></style>
