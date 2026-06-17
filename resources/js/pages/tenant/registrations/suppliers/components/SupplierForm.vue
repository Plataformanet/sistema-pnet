<script setup lang="ts">
import { ref, watch } from "vue";
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
import { maskCPF, maskCNPJ, maskPhone, maskCEP, handleMask } from "@/lib/masks";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

import { useForm } from "@inertiajs/vue3";
import { Textarea } from "@/components/ui/textarea";
import { useCepLookup } from "@/composables/useCepLookup";
import { useContactLookup } from "@/composables/useContactLookup";
import { UFS_LIST } from "@/lib/constants";

const ufs = UFS_LIST;



const props = withDefaults(
    defineProps<{
        form: ReturnType<typeof useForm>;
        submitText?: string;
        isEdit?: boolean;
    }>(),
    {
        submitText: "Salvar Fornecedor",
        isEdit: false,
    },
);

const emit = defineEmits(["submit"]);

useCepLookup(props.form as any);
useContactLookup(props.form, "suppliers", props.isEdit);

const supplierType = ref<"PF" | "PJ">(props.form.type || "PJ");

watch(supplierType, (val) => {
    props.form.type = val;
    if (typeof props.form.clearErrors === "function") {
        props.form.clearErrors();
    }
});

function onSubmit() {
    emit("submit");
}
</script>

<template>
    <div
        class="mb-8 inline-flex cursor-pointer rounded-md bg-muted p-1 transition-colors duration-200"
    >
        <button
            type="button"
            @click="supplierType = 'PJ'"
            class="rounded-sm px-5 py-2 text-sm font-semibold transition-all focus:outline-none"
            :class="
                supplierType === 'PJ'
                    ? 'bg-background text-foreground shadow'
                    : 'text-muted-foreground hover:bg-muted-foreground/10'
            "
        >
            Pessoa Jurídica
        </button>
        <button
            type="button"
            @click="supplierType = 'PF'"
            class="rounded-sm px-5 py-2 text-sm font-semibold transition-all focus:outline-none"
            :class="
                supplierType === 'PF'
                    ? 'bg-background text-foreground shadow'
                    : 'text-muted-foreground hover:bg-muted-foreground/10'
            "
        >
            Pessoa Física
        </button>
    </div>

    <form
        @submit.prevent="onSubmit"
        class="space-y-8 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8"
    >
        <!-- Dados Principais -->
        <div class="mb-8">
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">
                Dados Principais
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <template v-if="supplierType === 'PF'">
                    <Field>
                        <FieldLabel for="cpf_cnpj">CPF *</FieldLabel>
                        <Input
                            id="cpf_cnpj"
                            :model-value="form.cpf_cnpj"
                            @input="handleMask($event, maskCPF, val => form.cpf_cnpj = val)"
                            required
                            placeholder="000.000.000-00"
                            maxlength="14"
                        />
                        <FieldError v-if="form.errors.cpf_cnpj">{{
                            form.errors.cpf_cnpj
                        }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="name_corporatereason">Nome Completo *</FieldLabel>
                        <Input id="name_corporatereason" v-model="form.name_corporatereason" required />
                        <FieldError v-if="form.errors.name_corporatereason">{{
                            form.errors.name_corporatereason
                        }}</FieldError>
                    </Field>
                </template>

                <template v-if="supplierType === 'PJ'">
                    <Field>
                        <FieldLabel for="cpf_cnpj">CNPJ *</FieldLabel>
                        <Input
                            id="cpf_cnpj"
                            :model-value="form.cpf_cnpj"
                            @input="handleMask($event, maskCNPJ, val => form.cpf_cnpj = val)"
                            required
                            placeholder="00.000.000/0001-00"
                            maxlength="18"
                        />
                        <FieldError v-if="form.errors.cpf_cnpj">{{
                            form.errors.cpf_cnpj
                        }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="name_corporatereason"
                            >Razão Social *</FieldLabel
                        >
                        <Input
                            id="name_corporatereason"
                            v-model="form.name_corporatereason"
                            required
                        />
                        <FieldError v-if="form.errors.name_corporatereason">{{
                            form.errors.name_corporatereason
                        }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="fantasy_name"
                            >Nome Fantasia</FieldLabel
                        >
                        <Input id="fantasy_name" v-model="form.fantasy_name" />
                        <FieldError v-if="form.errors.fantasy_name">{{
                            form.errors.fantasy_name
                        }}</FieldError>
                    </Field>
                </template>

                <Field :class="supplierType === 'PF' ? 'md:col-span-2' : ''">
                    <FieldLabel for="responsible_person">Nome do Contato</FieldLabel>
                    <Input
                        id="responsible_person"
                        v-model="form.responsible_person"
                        placeholder="Pessoa responsável"
                    />
                    <FieldError v-if="form.errors.responsible_person">{{
                        form.errors.responsible_person
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-2">
                    <FieldLabel for="description">Descrição *</FieldLabel>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        placeholder="Ex: Equipamentos, Serviços de TI, Limpeza..."
                        required
                    />
                    <FieldError v-if="form.errors.description">{{
                        form.errors.description
                    }}</FieldError>
                </Field>
                <Field class="md:col-span-2">
                    <FieldLabel for="supply_category"
                        >Categorias de Fornecimento *</FieldLabel
                    >
                    <Input
                        id="supply_category"
                        v-model="form.supply_category"
                        placeholder="Ex: Equipamentos, Serviços de TI, Limpeza..."
                        required
                    />
                    <FieldError v-if="form.errors.supply_category">{{
                        form.errors.supply_category
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-2">
                    <FieldLabel for="email">E-mail *</FieldLabel>
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        placeholder="email@exemplo.com"
                    />
                    <FieldError v-if="form.errors.email">{{
                        form.errors.email
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="phone">Telefone *</FieldLabel>
                    <Input
                        id="phone"
                        :model-value="form.phone"
                        @input="handleMask($event, maskPhone, val => form.phone = val)"
                        placeholder="(00) 0000-0000"
                        maxlength="15"
                        required
                    />
                    <FieldError v-if="form.errors.phone">{{
                        form.errors.phone
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="cell_phone">Celular *</FieldLabel>
                    <Input
                        id="cell_phone"
                        :model-value="form.cell_phone"
                        @input="handleMask($event, maskPhone, val => form.cell_phone = val)"
                        placeholder="(00) 00000-0000"
                        maxlength="15"
                        required
                    />
                    <FieldError v-if="form.errors.cell_phone">{{
                        form.errors.cell_phone
                    }}</FieldError>
                </Field>
            </div>
        </div>

        <div class="mb-4 border-t border-border pt-4 pb-4">
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">
                Endereço
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
                <Field class="md:col-span-3">
                    <FieldLabel for="zip_code">CEP *</FieldLabel>
                    <Input
                        id="zip_code"
                        :model-value="form.zip_code"
                        @input="handleMask($event, maskCEP, val => form.zip_code = val)"
                        placeholder="00000-000"
                        maxlength="9"
                        required
                    />
                    <FieldError v-if="form.errors.zip_code">{{
                        form.errors.zip_code
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-7">
                    <FieldLabel for="street">Logradouro *</FieldLabel>
                    <Input id="street" v-model="form.street" required />
                    <FieldError v-if="form.errors.street">{{
                        form.errors.street
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-2">
                    <FieldLabel for="number">Número *</FieldLabel>
                    <Input id="number" v-model="form.number" required />
                    <FieldError v-if="form.errors.number">{{
                        form.errors.number
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-3">
                    <FieldLabel for="complement">Complemento</FieldLabel>
                    <Input
                        id="complement"
                        v-model="form.complement"
                        placeholder="Apto, Sala..."
                    />
                    <FieldError v-if="form.errors.complement">{{
                        form.errors.complement
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-3">
                    <FieldLabel for="neighborhood">Bairro *</FieldLabel>
                    <Input id="neighborhood" v-model="form.neighborhood" required />
                    <FieldError v-if="form.errors.neighborhood">{{
                        form.errors.neighborhood
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-4">
                    <FieldLabel for="city">Cidade *</FieldLabel>
                    <Input id="city" v-model="form.city" required />
                    <FieldError v-if="form.errors.city">{{
                        form.errors.city
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-2">
                    <FieldLabel for="state">UF *</FieldLabel>
                    <Select :model-value="form.state" @update:model-value="form.state = $event">
                        <SelectTrigger id="state">
                            <SelectValue placeholder="UF" />
                        </SelectTrigger>
                        <SelectContent side="bottom">
                            <SelectGroup>
                                <SelectItem v-for="uf in ufs" :key="uf" :value="uf">
                                    {{ uf }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.state">{{
                        form.errors.state
                    }}</FieldError>
                </Field>
            </div>
        </div>

        <div class="flex justify-end border-t border-border pt-6">
            <Button
                type="submit"
                class="text-md w-full px-10 font-bold md:w-auto"
                :loading="form.processing"
                :disabled="form.processing"
            >
                {{ submitText }}
            </Button>
        </div>
    </form>
</template>
