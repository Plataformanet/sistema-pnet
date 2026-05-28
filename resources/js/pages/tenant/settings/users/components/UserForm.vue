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

// Dados mockados para garantir o funcionamento e renderização imediata sem depender do back-end
const mockPermissions: Permission[] = [
    // Cadastros
    { name: 'registrations.clients.view', display_name: 'Clientes (Visualizar)' },
    { name: 'registrations.clients.edit', display_name: 'Clientes (Editar)' },
    { name: 'registrations.clients.create', display_name: 'Clientes (Criar)' },
    { name: 'registrations.clients.delete', display_name: 'Clientes (Excluir)' },
    { name: 'registrations.suppliers.view', display_name: 'Fornecedores (Visualizar)' },
    { name: 'registrations.suppliers.edit', display_name: 'Fornecedores (Editar)' },
    { name: 'registrations.suppliers.create', display_name: 'Fornecedores (Criar)' },
    { name: 'registrations.suppliers.delete', display_name: 'Fornecedores (Excluir)' },
    { name: 'registrations.employees.view', display_name: 'Funcionários (Visualizar)' },
    { name: 'registrations.employees.edit', display_name: 'Funcionários (Editar)' },
    { name: 'registrations.employees.create', display_name: 'Funcionários (Criar)' },
    { name: 'registrations.employees.delete', display_name: 'Funcionários (Excluir)' },
    
    // Vendas
    { name: 'sales.sales.view', display_name: 'Vendas (Visualizar)' },
    { name: 'sales.sales.edit', display_name: 'Vendas (Editar)' },
    { name: 'sales.sales.create', display_name: 'Vendas (Criar)' },
    { name: 'sales.sales.delete', display_name: 'Vendas (Excluir)' },
    { name: 'sales.quotations.view', display_name: 'Orçamentos (Visualizar)' },
    { name: 'sales.quotations.edit', display_name: 'Orçamentos (Editar)' },
    { name: 'sales.quotations.create', display_name: 'Orçamentos (Criar)' },
    { name: 'sales.quotations.delete', display_name: 'Orçamentos (Excluir)' },

    // Serviços
    { name: 'services.services.view', display_name: 'Serviços (Visualizar)' },
    { name: 'services.services.edit', display_name: 'Serviços (Editar)' },
    { name: 'services.services.create', display_name: 'Serviços (Criar)' },
    { name: 'services.services.delete', display_name: 'Serviços (Excluir)' },
    { name: 'services.categories.view', display_name: 'Categorias de Serviços (Visualizar)' },
    { name: 'services.categories.edit', display_name: 'Categorias de Serviços (Editar)' },
    { name: 'services.categories.create', display_name: 'Categorias de Serviços (Criar)' },
    { name: 'services.categories.delete', display_name: 'Categorias de Serviços (Excluir)' },

    // Produtos
    { name: 'products.products.view', display_name: 'Produtos (Visualizar)' },
    { name: 'products.products.edit', display_name: 'Produtos (Editar)' },
    { name: 'products.products.create', display_name: 'Produtos (Criar)' },
    { name: 'products.products.delete', display_name: 'Produtos (Excluir)' },
    { name: 'products.categories.view', display_name: 'Categorias de Produtos (Visualizar)' },
    { name: 'products.categories.edit', display_name: 'Categorias de Produtos (Editar)' },
    { name: 'products.categories.create', display_name: 'Categorias de Produtos (Criar)' },
    { name: 'products.categories.delete', display_name: 'Categorias de Produtos (Excluir)' },

    // Financeiro
    { name: 'finance.categories.view', display_name: 'Categorias Financeiras (Visualizar)' },
    { name: 'finance.categories.edit', display_name: 'Categorias Financeiras (Editar)' },
    { name: 'finance.categories.create', display_name: 'Categorias Financeiras (Criar)' },
    { name: 'finance.categories.delete', display_name: 'Categorias Financeiras (Excluir)' },
    { name: 'finance.cash_flow.view', display_name: 'Fluxo de Caixa (Visualizar)' },
    { name: 'finance.expenses_flow.view', display_name: 'Fluxo de Despesas (Visualizar)' },
    { name: 'finance.billing.view', display_name: 'Faturamento (Visualizar)' },

    // Documentações
    { name: 'documents.proposals.view', display_name: 'Propostas (Visualizar)' },
    { name: 'documents.proposals.edit', display_name: 'Propostas (Editar)' },
    { name: 'documents.proposals.create', display_name: 'Propostas (Criar)' },
    { name: 'documents.proposals.delete', display_name: 'Propostas (Excluir)' },
    { name: 'documents.itbi_calculator.view', display_name: 'Calculadora de ITBI (Visualizar)' },

    // Configurações
    { name: 'settings.roles.view', display_name: 'Cargos (Visualizar)' },
    { name: 'settings.users.view', display_name: 'Usuários (Visualizar)' },
];

const mockRolesWithPermissions: Record<string, string[]> = {
    'Admin': mockPermissions.map(p => p.name),
    'Seller': [
        'registrations.clients.view', 'registrations.clients.create', 'registrations.clients.edit',
        'sales.sales.view', 'sales.sales.create', 'sales.sales.edit',
        'sales.quotations.view', 'sales.quotations.create', 'sales.quotations.edit',
    ],
    'Financial': [
        'finance.categories.view', 'finance.categories.create', 'finance.categories.edit',
        'finance.cash_flow.view', 'finance.expenses_flow.view', 'finance.billing.view',
    ],
    'Manager': [
        'registrations.clients.view', 'registrations.clients.create', 'registrations.clients.edit', 'registrations.clients.delete',
        'registrations.suppliers.view', 'registrations.suppliers.create', 'registrations.suppliers.edit', 'registrations.suppliers.delete',
        'registrations.employees.view', 'registrations.employees.create', 'registrations.employees.edit',
        'sales.sales.view', 'sales.sales.create', 'sales.sales.edit',
        'services.services.view', 'services.services.create', 'services.services.edit',
        'finance.cash_flow.view',
    ],
    'Partner': [
        'registrations.clients.view',
        'sales.sales.view',
        'services.services.view',
    ]
};

// Determina o conjunto ativo de permissões (recebidas ou mockadas como fallback)
const effectivePermissions = computed<Permission[]>(() => {
    const items = props.systemPermissions;
    // Garante que é um array de objetos válidos, caso contrário faz fallback para o mock
    return items && items.length > 0 && typeof items[0] === 'object' && 'name' in items[0]
        ? items
        : mockPermissions;
});

// Determina o mapeamento ativo de cargos com permissões (recebido ou mockado como fallback)
const effectiveRolesWithPermissions = computed<Record<string, string[]>>(() => {
    return props.rolesWithPermissions && Object.keys(props.rolesWithPermissions).length > 0 
        ? props.rolesWithPermissions 
        : mockRolesWithPermissions;
});

const groupLabels: Record<string, string> = {
    registrations: 'Cadastros',
    sales: 'Vendas',
    services: 'Serviços',
    products: 'Produtos',
    finance: 'Financeiro',
    documents: 'Documentações',
    settings: 'Configurações',
};

const permissionsGroups = computed(() => {
    const groups: Record<string, { name: string; items: { id: string; label: string }[] }> = {};

    for (const permission of effectivePermissions.value) {
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
    if (!effectiveRolesWithPermissions.value || !props.form.role) return [];
    return effectiveRolesWithPermissions.value[props.form.role] ?? [];
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
        <div v-if="effectivePermissions && effectivePermissions.length > 0" class="mb-4 border-t border-border pt-6">
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
                                :checked="isChecked(permission.id)"
                                :disabled="isInherited(permission.id)"
                                @update:checked="togglePermission(permission.id)"
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
