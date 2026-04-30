<script setup lang="ts">
import { ref, watch } from "vue";
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
import { maskCPF, maskCNPJ, maskPhone, maskCEP } from "@/lib/masks";

import { useForm } from "@inertiajs/vue3";
import { Textarea } from "@/components/ui/textarea";

const props = withDefaults(
    defineProps<{
        form: ReturnType<typeof useForm>;
        submitText?: string;
    }>(),
    {
        submitText: "Salvar Fornecedor",
    },
);

const emit = defineEmits(["submit"]);

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
                        <FieldLabel for="name">Nome Completo *</FieldLabel>
                        <Input id="name" v-model="form.name" required />
                        <FieldError v-if="form.errors.name">{{
                            form.errors.name
                        }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="cpf">CPF *</FieldLabel>
                        <Input
                            id="cpf"
                            :model-value="form.cpf"
                            @update:model-value="
                                form.cpf = maskCPF($event as string)
                            "
                            required
                            placeholder="000.000.000-00"
                            maxlength="14"
                        />
                        <FieldError v-if="form.errors.cpf">{{
                            form.errors.cpf
                        }}</FieldError>
                    </Field>
                </template>

                <template v-if="supplierType === 'PJ'">
                    <Field>
                        <FieldLabel for="corporate_reason"
                            >Razão Social *</FieldLabel
                        >
                        <Input
                            id="corporate_reason"
                            v-model="form.corporate_reason"
                            required
                        />
                        <FieldError v-if="form.errors.corporate_reason">{{
                            form.errors.corporate_reason
                        }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="cnpj">CNPJ *</FieldLabel>
                        <Input
                            id="cnpj"
                            :model-value="form.cnpj"
                            @update:model-value="
                                form.cnpj = maskCNPJ($event as string)
                            "
                            required
                            placeholder="00.000.000/0001-00"
                            maxlength="18"
                        />
                        <FieldError v-if="form.errors.cnpj">{{
                            form.errors.cnpj
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
                    <FieldLabel for="contact_name">Nome do Contato</FieldLabel>
                    <Input
                        id="contact_name"
                        v-model="form.contact_name"
                        placeholder="Pessoa responsável"
                    />
                    <FieldError v-if="form.errors.contact_name">{{
                        form.errors.contact_name
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-2">
                    <FieldLabel for="description">Descrição</FieldLabel>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        placeholder="Ex: Equipamentos, Serviços de TI, Limpeza..."
                    />
                    <FieldError v-if="form.errors.description">{{
                        form.errors.description
                    }}</FieldError>
                </Field>
                <Field class="md:col-span-2">
                    <FieldLabel for="categories"
                        >Categorias de Fornecimento</FieldLabel
                    >
                    <Input
                        id="categories"
                        v-model="form.categories"
                        placeholder="Ex: Equipamentos, Serviços de TI, Limpeza..."
                    />
                    <FieldError v-if="form.errors.categories">{{
                        form.errors.categories
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
                    <FieldLabel for="phone">Telefone</FieldLabel>
                    <Input
                        id="phone"
                        :model-value="form.phone"
                        @update:model-value="
                            form.phone = maskPhone($event as string)
                        "
                        placeholder="(00) 0000-0000"
                        maxlength="15"
                    />
                    <FieldError v-if="form.errors.phone">{{
                        form.errors.phone
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="cellphone">Celular</FieldLabel>
                    <Input
                        id="cellphone"
                        :model-value="form.cellphone"
                        @update:model-value="
                            form.cellphone = maskPhone($event as string)
                        "
                        placeholder="(00) 00000-0000"
                        maxlength="15"
                    />
                    <FieldError v-if="form.errors.cellphone">{{
                        form.errors.cellphone
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
                    <FieldLabel for="zipcode">CEP</FieldLabel>
                    <Input
                        id="zipcode"
                        :model-value="form.zipcode"
                        @update:model-value="
                            form.zipcode = maskCEP($event as string)
                        "
                        placeholder="00000-000"
                        maxlength="9"
                    />
                    <FieldError v-if="form.errors.zipcode">{{
                        form.errors.zipcode
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-7">
                    <FieldLabel for="street">Logradouro</FieldLabel>
                    <Input id="street" v-model="form.street" />
                    <FieldError v-if="form.errors.street">{{
                        form.errors.street
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-2">
                    <FieldLabel for="number">Número</FieldLabel>
                    <Input id="number" v-model="form.number" />
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

                <Field class="md:col-span-4">
                    <FieldLabel for="neighborhood">Bairro</FieldLabel>
                    <Input id="neighborhood" v-model="form.neighborhood" />
                    <FieldError v-if="form.errors.neighborhood">{{
                        form.errors.neighborhood
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-4">
                    <FieldLabel for="city">Cidade</FieldLabel>
                    <Input id="city" v-model="form.city" />
                    <FieldError v-if="form.errors.city">{{
                        form.errors.city
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-1">
                    <FieldLabel for="state">UF</FieldLabel>
                    <Input
                        id="state"
                        v-model="form.state"
                        maxlength="2"
                        placeholder="SP"
                    />
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
                :disabled="form.processing"
            >
                {{ submitText }}
            </Button>
        </div>
    </form>
</template>
