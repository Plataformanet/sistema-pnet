<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
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
} from '@/components/ui/select'

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    submitText: {
        type: String,
        default: "Salvar Usuário",
    },
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
                Dados do Usuário
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field class="md:col-span-2">
                    <FieldLabel for="name">Nome Completo *</FieldLabel>
                    <Input id="name" v-model="form.name" required />
                    <FieldError v-if="form.errors.name">{{ form.errors.name }}</FieldError>
                </Field>

                <Field class="md:col-span-2">
                    <FieldLabel for="email">E-mail (Login) *</FieldLabel>
                    <Input id="email" type="email" v-model="form.email" required placeholder="email@exemplo.com" />
                    <FieldError v-if="form.errors.email">{{ form.errors.email }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="role">Cargo / Perfil de Acesso *</FieldLabel>
                    <Select v-model="form.role" required>
                        <SelectTrigger id="role" class="w-full">
                            <SelectValue placeholder="Selecione um cargo..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="Administrador">Administrador</SelectItem>
                                <SelectItem value="Gerente">Gerente</SelectItem>
                                <SelectItem value="Financeiro">Financeiro</SelectItem>
                                <SelectItem value="Vendedor">Vendedor</SelectItem>
                                <SelectItem value="Suporte">Suporte</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground mt-1">Este cargo carregará as permissões do sistema.</p>
                    <FieldError v-if="form.errors.role">{{ form.errors.role }}</FieldError>
                </Field>

                
                <div class="flex items-center space-x-2 pt-8">
                    <!-- Placeholder UI that could be a Checkbox or Switch component later, using native for simplicity to avoid import issues without inspecting UI folder -->
                    <input type="checkbox" id="active" v-model="form.active" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary" />
                    <label for="active" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Usuário Ativo
                    </label>
                </div>
            </div>
        </div>

        <div class="mb-4 border-t border-border pt-4 pb-4">
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">
                Segurança
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field>
                    <FieldLabel for="password">Senha *</FieldLabel>
                    <Input id="password" type="password" v-model="form.password" />
                    <FieldError v-if="form.errors.password">{{ form.errors.password }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="password_confirmation">Confirmar Senha *</FieldLabel>
                    <Input id="password_confirmation" type="password" v-model="form.password_confirmation" />
                    <FieldError v-if="form.errors.password_confirmation">{{ form.errors.password_confirmation }}</FieldError>
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
