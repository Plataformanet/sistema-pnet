<script setup lang="ts">
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { CheckCircle2, Circle } from "lucide-vue-next";
import { Role } from '@/types';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    form: ReturnType<typeof useForm>;
    role?: Role;
    submitText?: string;
}>();

const emit = defineEmits(["submit"]);

const permissionsGroups = [
    {
        name: 'Cadastros',
        items: [
            { id: 'registrations.clients.view', label: 'Clientes (Visualizar)' },
            { id: 'registrations.clients.edit', label: 'Clientes (Editar)' },
            { id: 'registrations.clients.create', label: 'Clientes (Criar)' },
            { id: 'registrations.clients.delete', label: 'Clientes (Excluir)' },
            { id: 'registrations.suppliers.view', label: 'Fornecedores (Visualizar)' },
            { id: 'registrations.suppliers.edit', label: 'Fornecedores (Editar)' },
            { id: 'registrations.suppliers.create', label: 'Fornecedores (Criar)' },
            { id: 'registrations.suppliers.delete', label: 'Fornecedores (Excluir)' },
            { id: 'registrations.employees.view', label: 'Funcionários (Visualizar)' },
            { id: 'registrations.employees.edit', label: 'Funcionários (Editar)' },
            { id: 'registrations.employees.create', label: 'Funcionários (Criar)' },
            { id: 'registrations.employees.delete', label: 'Funcionários (Excluir)' },
        ]
    },
    {
        name: 'Vendas',
        items: [
            { id: 'sales.sales.view', label: 'Vendas (Visualizar)' },
            { id: 'sales.sales.edit', label: 'Vendas (Editar)' },
            { id: 'sales.sales.create', label: 'Vendas (Criar)' },
            { id: 'sales.sales.delete', label: 'Vendas (Excluir)' },
            { id: 'sales.quotations.view', label: 'Orçamentos (Visualizar)' },
            { id: 'sales.quotations.edit', label: 'Orçamentos (Editar)' },
            { id: 'sales.quotations.create', label: 'Orçamentos (Criar)' },
            { id: 'sales.quotations.delete', label: 'Orçamentos (Excluir)' },
        ]
    },
    {
        name: 'Serviços',
        items: [
            { id: 'services.services.view', label: 'Serviços (Visualizar)' },
            { id: 'services.services.edit', label: 'Serviços (Editar)' },
            { id: 'services.services.create', label: 'Serviços (Criar)' },
            { id: 'services.services.delete', label: 'Serviços (Excluir)' },
            { id: 'services.categories.view', label: 'Categorias de Serviços (Visualizar)' },
            { id: 'services.categories.edit', label: 'Categorias de Serviços (Editar)' },
            { id: 'services.categories.create', label: 'Categorias de Serviços (Criar)' },
            { id: 'services.categories.delete', label: 'Categorias de Serviços (Excluir)' },
        ]
    },
    {
        name: 'Produtos',
        items: [
            { id: 'products.products.view', label: 'Produtos (Visualizar)' },
            { id: 'products.products.edit', label: 'Produtos (Editar)' },
            { id: 'products.products.create', label: 'Produtos (Criar)' },
            { id: 'products.products.delete', label: 'Produtos (Excluir)' },
            { id: 'products.categories.view', label: 'Categorias de Produtos (Visualizar)' },
            { id: 'products.categories.edit', label: 'Categorias de Produtos (Editar)' },
            { id: 'products.categories.create', label: 'Categorias de Produtos (Criar)' },
            { id: 'products.categories.delete', label: 'Categorias de Produtos (Excluir)' },
        ]
    },
    {
        name: 'Financeiro',
        items: [
            { id: 'finance.categories.view', label: 'Categorias/Subcategorias (Visualizar)' },
            { id: 'finance.categories.edit', label: 'Categorias/Subcategorias (Editar)' },
            { id: 'finance.categories.create', label: 'Categorias/Subcategorias (Criar)' },
            { id: 'finance.categories.delete', label: 'Categorias/Subcategorias (Excluir)' },
            { id: 'finance.accounts.view', label: 'Contas Bancárias (Visualizar)' },
            { id: 'finance.accounts.edit', label: 'Contas Bancárias (Editar)' },
            { id: 'finance.accounts.create', label: 'Contas Bancárias (Criar)' },
            { id: 'finance.accounts.delete', label: 'Contas Bancárias (Excluir)' },
            { id: 'finance.accounts_payable.view', label: 'Contas a Pagar (Visualizar)' },
            { id: 'finance.accounts_payable.edit', label: 'Contas a Pagar (Editar)' },
            { id: 'finance.accounts_payable.create', label: 'Contas a Pagar (Criar)' },
            { id: 'finance.accounts_payable.delete', label: 'Contas a Pagar (Excluir)' },
            { id: 'finance.accounts_receivable.view', label: 'Contas a Receber (Visualizar)' },
            { id: 'finance.accounts_receivable.edit', label: 'Contas a Receber (Editar)' },
            { id: 'finance.accounts_receivable.create', label: 'Contas a Receber (Criar)' },
            { id: 'finance.accounts_receivable.delete', label: 'Contas a Receber (Excluir)' },
            { id: 'finance.cash_flow.view', label: 'Fluxo de Caixa (Visualizar)' },
            { id: 'finance.expenses_flow.view', label: 'Fluxo de Gastos (Visualizar)' },
            { id: 'finance.billing.view', label: 'Faturamentos (Visualizar)' },
        ]
    },
    {
        name: 'Documentações',
        items: [
            { id: 'documents.proposals.view', label: 'Propostas (Visualizar)' },
            { id: 'documents.proposals.edit', label: 'Propostas (Editar)' },
            { id: 'documents.proposals.create', label: 'Propostas (Criar)' },
            { id: 'documents.proposals.delete', label: 'Propostas (Excluir)' },
            { id: 'documents.itbi_calculator.view', label: 'Calculadora ITBI (Visualizar)' },
            { id: 'documents.itbi_calculator.edit', label: 'Calculadora ITBI (Editar)' },
            { id: 'documents.itbi_calculator.create', label: 'Calculadora ITBI (Criar)' },
            { id: 'documents.itbi_calculator.delete', label: 'Calculadora ITBI (Excluir)' },
        ]
    },
    {
        name: 'Configurações',
        items: [
            { id: 'settings.roles.view', label: 'Cargos (Visualizar)' },
            { id: 'settings.roles.edit', label: 'Cargos (Editar)' },
            { id: 'settings.roles.create', label: 'Cargos (Criar)' },
            { id: 'settings.roles.delete', label: 'Cargos (Excluir)' },
            { id: 'settings.users.view', label: 'Usuários (Visualizar)' },
            { id: 'settings.users.edit', label: 'Usuários (Editar)' },
            { id: 'settings.users.create', label: 'Usuários (Criar)' },
            { id: 'settings.users.delete', label: 'Usuários (Excluir)' },
        ]
    }
];

function togglePermission(id: string) {
    let newPermissions = [...props.form.permissions];
    const index = newPermissions.indexOf(id);
    if (index === -1) {
        newPermissions.push(id);
    } else {
        newPermissions.splice(index, 1);
    }
    props.form.permissions = newPermissions;
}
</script>

<template>
    <form @submit.prevent="emit('submit')">
        <div class="grid gap-6">
            <Card>
                <CardHeader>
                    <CardTitle>Informações do Cargo</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="name">Nome do Cargo <span class="text-red-500">*</span></Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Ex: Auxiliar Financeiro"
                            required
                        />
                    </div>
                </CardContent>
            </Card>

            <h3 class="text-xl font-bold tracking-tight text-foreground mt-4 border-b border-border pb-2">
                Permissões de Acesso
            </h3>
            
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
                        >
                            <Checkbox 
                                :id="permission.id" 
                                :checked="form.permissions.includes(permission.id)"
                                @update:checked="togglePermission(permission.id)"
                                class="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                            />
                            <span class="text-sm font-medium leading-none flex-1 group-hover/item:text-foreground text-muted-foreground transition-colors">
                                {{ permission.label }}
                            </span>
                        </Label>
                    </CardContent>
                </Card>
            </div>

            <div class="flex justify-end gap-4 mt-6">
                <Button type="button" variant="outline" @click="() => form.reset()">Limpar</Button>
                <Button type="submit">Salvar Cargo</Button>
            </div>
        </div>
    </form>
</template>
