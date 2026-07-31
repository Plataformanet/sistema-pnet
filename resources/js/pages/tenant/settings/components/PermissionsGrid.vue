<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { Permission } from "@/types";
import { computed } from "vue";

const props = withDefaults(
    defineProps<{
        modelValue: string[];
        permissions: Permission[];
        inheritedPermissions?: string[];
        title?: string;
        description?: string;
    }>(),
    {
        inheritedPermissions: () => [],
        title: "",
        description: "",
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: string[]): void;
}>();

const groupLabels: Record<string, string> = {
    registrations: "Cadastros",
    sales: "Vendas",
    services: "Serviços",
    products: "Produtos",
    finance: "Financeiro",
    documents: "Documentações",
    drive: "Drive",
    settings: "Configurações",
};

interface PermissionGroupItem {
    id: string;
    label: string;
}

interface PermissionGroup {
    name: string;
    items: PermissionGroupItem[];
}

const permissionsGroups = computed<PermissionGroup[]>(() => {
    const groups: Record<string, PermissionGroup> = {};

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

const isInherited = (permissionName: string) => {
    return props.inheritedPermissions.includes(permissionName);
};

const isChecked = (permissionName: string) => {
    return (
        isInherited(permissionName) ||
        props.modelValue.includes(permissionName)
    );
};

function togglePermission(permissionName: string) {
    if (isInherited(permissionName)) return;

    const current = [...props.modelValue];
    const index = current.indexOf(permissionName);
    if (index === -1) {
        current.push(permissionName);
    } else {
        current.splice(index, 1);
    }
    emit("update:modelValue", current);
}

function getSelectableItems(group: PermissionGroup) {
    return group.items.filter((item) => !isInherited(item.id));
}

function isGroupAllSelected(group: PermissionGroup) {
    const selectable = getSelectableItems(group);
    if (selectable.length === 0) return false;
    return selectable.every((item) => props.modelValue.includes(item.id));
}

function toggleSelectAllGroup(group: PermissionGroup) {
    const selectable = getSelectableItems(group);
    if (selectable.length === 0) return;

    const allSelected = isGroupAllSelected(group);
    const current = [...props.modelValue];

    if (allSelected) {
        // Desmarcar todas as permissões selecionáveis deste grupo
        const selectableIds = new Set(selectable.map((i) => i.id));
        const updated = current.filter((id) => !selectableIds.has(id));
        emit("update:modelValue", updated);
    } else {
        // Marcar todas as permissões selecionáveis deste grupo
        const updatedSet = new Set(current);
        for (const item of selectable) {
            updatedSet.add(item.id);
        }
        emit("update:modelValue", Array.from(updatedSet));
    }
}
</script>

<template>
    <div class="space-y-4">
        <div v-if="title || description" class="mb-4">
            <h3
                v-if="title"
                class="text-lg font-semibold text-card-foreground"
            >
                {{ title }}
            </h3>
            <p v-if="description" class="mt-1 text-xs text-muted-foreground">
                {{ description }}
            </p>
        </div>

        <div class="columns-1 gap-6 md:columns-2 xl:columns-3">
            <Card
                v-for="group in permissionsGroups"
                :key="group.name"
                class="mb-6 flex break-inside-avoid flex-col overflow-hidden border-border/60 py-0 transition-all duration-200 hover:border-primary/30 hover:shadow-md"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between gap-0 border-b border-border/40 bg-muted/30 px-4 [.border-b]:py-2.5"
                >
                    <CardTitle class="text-base font-semibold text-foreground">
                        {{ group.name }}
                    </CardTitle>
                    <button
                        type="button"
                        v-if="getSelectableItems(group).length > 0"
                        @click="toggleSelectAllGroup(group)"
                        class="cursor-pointer text-xs font-semibold text-primary transition-colors hover:text-primary/80 hover:underline focus:outline-none"
                    >
                        {{
                            isGroupAllSelected(group)
                                ? "Desmarcar todos"
                                : "Selecionar todos"
                        }}
                    </button>
                </CardHeader>

                <CardContent class="flex flex-col gap-1.5 p-4">
                    <Label
                        v-for="permission in group.items"
                        :key="permission.id"
                        class="group/item flex cursor-pointer items-center space-x-3 rounded-md p-2 font-normal transition-colors hover:bg-accent/50"
                        :class="{
                            'cursor-not-allowed bg-muted/20 opacity-80':
                                isInherited(permission.id),
                        }"
                    >
                        <Checkbox
                            :id="permission.id"
                            :model-value="isChecked(permission.id)"
                            :disabled="isInherited(permission.id)"
                            @update:model-value="
                                togglePermission(permission.id)
                            "
                            class="data-[state=checked]:border-primary data-[state=checked]:bg-primary"
                        />
                        <span
                            class="flex flex-1 items-center justify-between text-sm leading-none transition-colors"
                            :class="
                                isInherited(permission.id)
                                    ? 'text-muted-foreground/80'
                                    : 'text-muted-foreground group-hover/item:text-foreground'
                            "
                        >
                            <span>{{ permission.label }}</span>
                            <span
                                v-if="isInherited(permission.id)"
                                class="ml-2 rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-semibold tracking-wider text-primary uppercase"
                            >
                                Cargo
                            </span>
                        </span>
                    </Label>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
