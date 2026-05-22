<script setup lang="ts">
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Role } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Permission {
    name: string;
    display_name: string;
}

const props = defineProps<{
    form: ReturnType<typeof useForm>;
    permissions: Permission[];
    role?: Role;
    submitText?: string;
}>();

const emit = defineEmits(["submit"]);

// Rótulos dos grupos por módulo (primeiro segmento de `module.resource.action`)
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

    for (const permission of props.permissions) {
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
                                :model-value="form.permissions.includes(permission.id)"
                                @update:model-value="togglePermission(permission.id)"
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
