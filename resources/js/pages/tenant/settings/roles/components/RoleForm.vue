<script setup lang="ts">
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Permission, Role } from "@/types";
import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps<{
    form: ReturnType<typeof useForm>;
    permissions: Permission[];
    role?: Role;
    submitText?: string;
}>();

const emit = defineEmits(["submit"]);

// Rótulos dos grupos por módulo (primeiro segmento de `module.resource.action`)
const groupLabels: Record<string, string> = {
    registrations: "Cadastros",
    sales: "Vendas",
    services: "Serviços",
    products: "Produtos",
    finance: "Financeiro",
    documents: "Documentações",
    settings: "Configurações",
};

const permissionsGroups = computed(() => {
    const groups: Record<
        string,
        { name: string; items: { id: string; label: string }[] }
    > = {};

    for (const permission of props.permissions) {
        const moduleKey = permission.name.split(".")[0];

        if (!groups[moduleKey]) {
            groups[moduleKey] = {
                name: groupLabels[moduleKey] ?? moduleKey,
                items: [],
            };
        }

        groups[moduleKey].items.push({
            id: permission.name,
            label: permission.display_name,
        });
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
                        <Label for="name"
                            >Nome do Cargo
                            <span class="text-red-500">*</span></Label
                        >
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Ex: Auxiliar Financeiro"
                            required
                        />
                    </div>
                </CardContent>
            </Card>

            <h3
                class="mt-4 border-b border-border pb-2 text-xl font-bold tracking-tight text-foreground"
            >
                Permissões de Acesso
            </h3>

            <div class="columns-1 gap-6 md:columns-2 xl:columns-3">
                <Card
                    v-for="group in permissionsGroups"
                    :key="group.name"
                    class="mb-6 flex break-inside-avoid flex-col overflow-hidden border-border/60 transition-all duration-200 hover:border-primary/30 hover:shadow-md"
                >
                    <CardHeader
                        class="border-b border-border/40 bg-muted/30 px-4 py-3 pb-3"
                    >
                        <CardTitle
                            class="text-base font-semibold text-foreground"
                        >
                            {{ group.name }}
                        </CardTitle>
                    </CardHeader>

                    <CardContent class="flex flex-col gap-1.5 p-4">
                        <Label
                            v-for="permission in group.items"
                            :key="permission.id"
                            class="group/item flex cursor-pointer items-center space-x-3 rounded-md p-2 font-normal transition-colors hover:bg-accent/50"
                        >
                            <Checkbox
                                :id="permission.id"
                                :model-value="
                                    form.permissions.includes(permission.id)
                                "
                                @update:model-value="
                                    togglePermission(permission.id)
                                "
                                class="data-[state=checked]:border-primary data-[state=checked]:bg-primary"
                            />
                            <span
                                class="flex-1 text-sm leading-none font-medium text-muted-foreground transition-colors group-hover/item:text-foreground"
                            >
                                {{ permission.label }}
                            </span>
                        </Label>
                    </CardContent>
                </Card>
            </div>

            <div class="mt-6 flex justify-end gap-4">
                <Button
                    type="button"
                    variant="outline"
                    @click="() => form.reset()"
                    >Limpar</Button
                >
                <Button type="submit" :loading="form.processing" :disabled="form.processing">Salvar Cargo</Button>
            </div>
        </div>
    </form>
</template>
