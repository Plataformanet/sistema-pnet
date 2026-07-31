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
} from "@/components/ui/select";
import PermissionsGrid from "@/pages/tenant/settings/components/PermissionsGrid.vue";
import { Permission } from "@/types";
import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";

const props = withDefaults(
    defineProps<{
        form: ReturnType<typeof useForm>;
        roles: string[];
        systemPermissions?: Permission[];
        rolesWithPermissions?: Record<string, string[]>;
        submitText?: string;
    }>(),
    {
        submitText: "Salvar Usuário",
        systemPermissions: () => [],
        rolesWithPermissions: () => ({}),
    },
);

const emit = defineEmits(["submit"]);

// Garante que o array de permissões exista no form para evitar erros de reatividade
if (!props.form.permissions) {
    props.form.permissions = [];
}

const inheritedPermissions = computed<string[]>(() => {
    if (!props.form.role) return [];
    return props.rolesWithPermissions[props.form.role] ?? [];
});

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
                    <FieldError v-if="form.errors.name">{{
                        form.errors.name
                    }}</FieldError>
                </Field>

                <Field class="md:col-span-2">
                    <FieldLabel for="email">E-mail (Login) *</FieldLabel>
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
                    <FieldLabel for="role">Cargo / Perfil de Acesso *</FieldLabel>
                    <Select v-model="form.role" required>
                        <SelectTrigger id="role" class="w-full">
                            <SelectValue placeholder="Selecione um cargo..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem
                                    v-for="role in roles"
                                    :key="role"
                                    :value="role"
                                >
                                    {{ role }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Este cargo carregará as permissões do sistema.
                    </p>
                    <FieldError v-if="form.errors.role">{{
                        form.errors.role
                    }}</FieldError>
                </Field>

                <div class="flex items-center space-x-2 pt-8">
                    <input
                        type="checkbox"
                        id="status"
                        v-model="form.status"
                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                    />
                    <label
                        for="status"
                        class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
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
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                    />
                    <FieldError v-if="form.errors.password">{{
                        form.errors.password
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="password_confirmation"
                        >Confirmar Senha *</FieldLabel
                    >
                    <Input
                        id="password_confirmation"
                        type="password"
                        v-model="form.password_confirmation"
                    />
                    <FieldError v-if="form.errors.password_confirmation">{{
                        form.errors.password_confirmation
                    }}</FieldError>
                </Field>
            </div>
        </div>

        <!-- Permissões Adicionais -->
        <PermissionsGrid
            v-if="systemPermissions.length > 0"
            v-model="form.permissions"
            :permissions="systemPermissions"
            :inherited-permissions="inheritedPermissions"
            title="Permissões Adicionais (Opcional)"
            description="Selecione permissões extras para este usuário. As permissões herdadas do cargo selecionado estarão marcadas e desabilitadas."
            class="border-t border-border pt-6"
        />

        <div class="flex justify-end border-t border-border pt-6">
            <Button
                type="submit"
                class="text-md w-full px-10 font-bold md:w-auto"
                :loading="form.processing"
            >
                {{ submitText }}
            </Button>
        </div>
    </form>
</template>
