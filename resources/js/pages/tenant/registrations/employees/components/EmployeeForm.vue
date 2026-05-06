<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
import { maskCPF, maskPhone, maskCEP } from "@/lib/masks";

import { useForm } from "@inertiajs/vue3";

const props = withDefaults(defineProps<{
    form: ReturnType<typeof useForm>;
    submitText?: string;
}>(), {
    submitText: "Salvar Funcionário",
});

const emit = defineEmits(["submit"]);

function onSubmit() {
    emit("submit");
}
</script>

<template>
    <form
        @submit.prevent="onSubmit"
        class="space-y-8 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8"
    >
        <!-- Dados Principais -->
        <div class="mb-4">
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">
                Dados Pessoais
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="name_corporatereason">Nome Completo *</FieldLabel>
                    <Input id="name_corporatereason" v-model="form.name_corporatereason" required />
                    <FieldError v-if="form.errors.name_corporatereason">{{ form.errors.name_corporatereason }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="cpf">CPF *</FieldLabel>
                    <Input
                        id="cpf_cnpj"
                        :model-value="form.cpf_cnpj"
                        @update:model-value="form.cpf_cnpj = maskCPF($event as string)"
                        required
                        placeholder="000.000.000-00"
                        maxlength="14"
                    />
                    <FieldError v-if="form.errors.cpf_cnpj">{{ form.errors.cpf_cnpj }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="rg">RG</FieldLabel>
                    <Input id="rg" v-model="form.rg" placeholder="00.000.000-0" />
                    <FieldError v-if="form.errors.rg">{{ form.errors.rg }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="birth_date">Data de Nascimento</FieldLabel>
                    <Input id="birth_date" type="date" v-model="form.birth_date" />
                    <FieldError v-if="form.errors.birth_date">{{ form.errors.birth_date }}</FieldError>
                </Field>

                <!-- Spacer for grid alignment if needed, or another field -->
            </div>
        </div>

        <div class="mb-4 border-t border-border pt-4 pb-4">
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">
                Dados Profissionais
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field>
                    <FieldLabel for="position">Cargo</FieldLabel>
                    <Input id="position" v-model="form.position" placeholder="Ex: Vendedor, Assistente Administrativo..." />
                    <FieldError v-if="form.errors.position">{{ form.errors.position }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="salary">Salário Bruto</FieldLabel>
                    <!-- In a real app we might want a mask for money, simplified here -->
                    <Input id="salary" v-model="form.salary" placeholder="R$ 0,00" />
                    <FieldError v-if="form.errors.salary">{{ form.errors.salary }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="hire_date">Data de Admissão</FieldLabel>
                    <Input id="hire_date" type="date" v-model="form.hire_date" />
                    <FieldError v-if="form.errors.hire_date">{{ form.errors.hire_date }}</FieldError>
                </Field>
            </div>
        </div>

        <div class="mb-4 border-t border-border pt-4 pb-4">
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">
                Contato
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="email">E-mail *</FieldLabel>
                    <Input id="email" type="email" v-model="form.email" required placeholder="email@exemplo.com" />
                    <FieldError v-if="form.errors.email">{{ form.errors.email }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="phone">Telefone Fixo</FieldLabel>
                    <Input
                        id="phone"
                        :model-value="form.phone"
                        @update:model-value="form.phone = maskPhone($event as string)"
                        placeholder="(00) 0000-0000"
                        maxlength="15"
                    />
                    <FieldError v-if="form.errors.phone">{{ form.errors.phone }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="cell_phone">Celular</FieldLabel>
                    <Input
                        id="cell_phone"
                        :model-value="form.cell_phone"
                        @update:model-value="form.cell_phone = maskPhone($event as string)"
                        placeholder="(00) 00000-0000"
                        maxlength="15"
                    />
                    <FieldError v-if="form.errors.cell_phone">{{ form.errors.cell_phone }}</FieldError>
                </Field>
            </div>
        </div>

        <div class="mb-4 border-t border-border pt-4 pb-4">
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">
                Endereço
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
                <Field class="md:col-span-3">
                    <FieldLabel for="zip_code">CEP</FieldLabel>
                    <Input
                        id="zip_code"
                        :model-value="form.zip_code"
                        @update:model-value="form.zip_code = maskCEP($event as string)"
                        placeholder="00000-000"
                        maxlength="9"
                    />
                    <FieldError v-if="form.errors.zip_code">{{ form.errors.zip_code }}</FieldError>
                </Field>

                <Field class="md:col-span-7">
                    <FieldLabel for="street">Logradouro</FieldLabel>
                    <Input id="street" v-model="form.street" />
                    <FieldError v-if="form.errors.street">{{ form.errors.street }}</FieldError>
                </Field>

                <Field class="md:col-span-2">
                    <FieldLabel for="number">Número</FieldLabel>
                    <Input id="number" v-model="form.number" />
                    <FieldError v-if="form.errors.number">{{ form.errors.number }}</FieldError>
                </Field>

                <Field class="md:col-span-3">
                    <FieldLabel for="complement">Complemento</FieldLabel>
                    <Input id="complement" v-model="form.complement" placeholder="Apto, Sala..." />
                    <FieldError v-if="form.errors.complement">{{ form.errors.complement }}</FieldError>
                </Field>

                <Field class="md:col-span-4">
                    <FieldLabel for="neighborhood">Bairro</FieldLabel>
                    <Input id="neighborhood" v-model="form.neighborhood" />
                    <FieldError v-if="form.errors.neighborhood">{{ form.errors.neighborhood }}</FieldError>
                </Field>

                <Field class="md:col-span-4">
                    <FieldLabel for="city">Cidade</FieldLabel>
                    <Input id="city" v-model="form.city" />
                    <FieldError v-if="form.errors.city">{{ form.errors.city }}</FieldError>
                </Field>

                <Field class="md:col-span-1">
                    <FieldLabel for="state">UF</FieldLabel>
                    <Input id="state" v-model="form.state" maxlength="2" placeholder="SP" />
                    <FieldError v-if="form.errors.state">{{ form.errors.state }}</FieldError>
                </Field>
            </div>
        </div>

        <div class="flex justify-end border-t border-border pt-6">
            <Button type="submit" class="text-md w-full px-10 font-bold md:w-auto" :disabled="form.processing">
                {{ submitText }}
            </Button>
        </div>
    </form>
</template>
