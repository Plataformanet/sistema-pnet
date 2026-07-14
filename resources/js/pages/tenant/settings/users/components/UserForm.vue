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
} from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";

import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";

interface Permission {
    name: string;
    display_name: string;
}

const props = withDefaults(defineProps<{
    form: ReturnType<typeof useForm>;
    roles: string[];
    systemPermissions?: Permission[];
    rolesWithPermissions?: Record<string, string[]>;
    submitText?: string;
}>(), {
    submitText: "Salvar Usuário",
    systemPermissions: () => [],
    rolesWithPermissions: () => ({}),
});

const emit = defineEmits(["submit"]);

// Garante que o array de permissões exista no form para evitar erros de reatividade
if (!props.form.permissions) {
    props.form.permissions = [];
}

const groupLabels: Record<string, string> = {
    registrations: 'Cadastros',
    sales: 'Vendas',
    services: 'Serviços',
    products: 'Produtos',
    finance: 'Financeiro',
    documents: 'Documentações',
    drive: 'Drive',
    settings: 'Configurações',
};

const permissionsGroups = computed(() => {
    const groups: Record<string, { name: string; items: { id: string; label: string }[] }> = {};

    for (const permission of props.systemPermissions) {
        const moduleKey = permission.name.split('.')[0];

        if (!groups[moduleKey]) {
            groups[moduleKey] = {
                name: groupLabels[moduleKey] ?? moduleKey,
                items: [],
            };
        }

        groups[moduleKey].items.push({ id: permission.name, label: permission.display_name });
    }

    const order = Object.keys(groupLabels);

    return Object.entries(groups)
        .sort(([a], [b]) => {
            const ia = order.indexOf(a);
            const ib = order.indexOf(b);
            return (ia === -1 ? Infinity : ia) - (ib === -1 ? Infinity : ib);
        })
        .map(([, group]) => group);
});

const inheritedPermissions = computed<string[]>(() => {
    if (!props.form.role) return [];
    return props.rolesWithPermissions[props.form.role] ?? [];
});

const isInherited = (permissionName: string) => {
    return inheritedPermissions.value.includes(permissionName);
};

const isChecked = (permissionName: string) => {
    return isInherited(permissionName) || (props.form.permissions as string[])?.includes(permissionName);
};

function togglePermission(name: string) {
    if (isInherited(name)) return;

    const permissionsList = props.form.permissions as string[];
    const index = permissionsList.indexOf(name);
    if (index === -1) {
        permissionsList.push(name);
    } else {
        permissionsList.splice(index, 1);
    }
}

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
                                <SelectItem v-for="role in roles" :key="role" :value="role">
                                    {{ role }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground mt-1">Este cargo carregará as permissões do sistema.</p>
                    <FieldError v-if="form.errors.role">{{ form.errors.role }}</FieldError>
                </Field>


                <div class="flex items-center space-x-2 pt-8">
                    <!-- Placeholder UI that could be a Checkbox or Switch component later, using native for simplicity to avoid import issues without inspecting UI folder -->
                    <input type="checkbox" id="status" v-model="form.status" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary" />
                    <label for="status" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
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

        <!-- Permissões Adicionais -->
        <div v-if="systemPermissions.length > 0" class="mb-4 border-t border-border pt-6">
            <h3 class="text-lg font-semibold text-card-foreground">
                Permissões Adicionais (Opcional)
            </h3>
            <p class="text-xs text-muted-foreground mt-1 mb-6">
                Selecione permissões extras para este usuário. As permissões herdadas do cargo selecionado estarão marcadas e desabilitadas.
            </p>

            <div class="columns-1 md:columns-2 xl:columns-3 gap-6">
                <Card v-for="group in permissionsGroups" :key="group.name" class="mb-6 break-inside-avoid transition-all duration-200 hover:shadow-md border-border/60 hover:border-primary/30 flex flex-col overflow-hidden">
                    <CardHeader class="pb-3 bg-muted/30 border-b border-border/40 px-4 py-3">
                        <CardTitle class="text-base font-semibold text-foreground">
                            {{ group.name }}
                        </CardTitle>
                    </CardHeader>

                    <CardContent class="p-4 flex flex-col gap-1.5">
                        <Label
                            v-for="permission in group.items"
                            :key="permission.id"
                            class="flex items-center space-x-3 group/item rounded-md p-2 hover:bg-accent/50 transition-colors cursor-pointer font-normal"
                            :class="{ 'opacity-80 bg-muted/20 cursor-not-allowed': isInherited(permission.id) }"
                        >
                            <Checkbox
                                :id="permission.id"
                                :model-value="isChecked(permission.id)"
                                :disabled="isInherited(permission.id)"
                                @update:model-value="togglePermission(permission.id)"
                                class="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                            />
                            <span class="text-sm font-medium leading-none flex-1 transition-colors flex items-center justify-between"
                                  :class="isInherited(permission.id) ? 'text-muted-foreground/80' : 'group-hover/item:text-foreground text-muted-foreground'">
                                <span>{{ permission.label }}</span>
                                <span v-if="isInherited(permission.id)" class="text-[10px] bg-primary/10 text-primary px-1.5 py-0.5 rounded font-semibold uppercase tracking-wider ml-2">
                                    Cargo
                                </span>
                            </span>
                        </Label>
                    </CardContent>
                </Card>
            </div>
        </div>

        <div class="flex justify-end border-t border-border pt-6">
            <Button type="submit" class="text-md w-full px-10 font-bold md:w-auto" :loading="form.processing">
                {{ submitText }}
            </Button>
        </div>
    </form>
</template>
