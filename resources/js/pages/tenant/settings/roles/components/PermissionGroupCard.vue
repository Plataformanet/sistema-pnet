<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { computed } from "vue";
import { CheckCircle2, Circle } from "lucide-vue-next";
import { Button } from "@/components/ui/button";

const props = defineProps<{
    group: {
        name: string;
        items: { id: string; label: string }[];
    };
}>();

const modelValue = defineModel<string[]>({ default: () => [] });

function togglePermission(id: string) {
    if (modelValue.value.includes(id)) {
        modelValue.value = modelValue.value.filter(itemId => itemId !== id);
    } else {
        modelValue.value = [...modelValue.value, id];
    }
}

// Propriedade computada para verificar se todas as opções deste grupo estão marcadas
const allSelected = computed(() => {
    if (props.group.items.length === 0) return false;
    return props.group.items.every(item => modelValue.value.includes(item.id));
});

function toggleAll() {
    if (allSelected.value) {
        // Remove todas as permissões deste grupo
        modelValue.value = modelValue.value.filter(id => !props.group.items.some(item => item.id === id));
    } else {
        // Adiciona todas as permissões deste grupo que ainda não estão no array
        const newItems = props.group.items
            .map(item => item.id)
            .filter(id => !modelValue.value.includes(id));
            
        modelValue.value = [...modelValue.value, ...newItems];
    }
}
</script>

<template>
    <Card class="h-full transition-all duration-200 hover:shadow-md border-border/60 hover:border-primary/30 flex flex-col overflow-hidden">
        <CardHeader class="pb-3 bg-muted/30 border-b border-border/40 flex flex-row items-center justify-between space-y-0 px-4 py-3">
            <CardTitle class="text-base font-semibold flex items-center gap-2 text-foreground">
                <CheckCircle2 v-if="allSelected" class="w-4 h-4 text-primary" />
                <Circle v-else class="w-4 h-4 text-muted-foreground/40" />
                {{ group.name }}
            </CardTitle>
            <Button 
                variant="ghost" 
                size="sm" 
                type="button"
                class="h-6 px-2 text-xs hover:bg-transparent hover:text-primary transition-colors" 
                @click="toggleAll"
            >
                {{ allSelected ? 'Desmarcar todos' : 'Marcar todos' }}
            </Button>
        </CardHeader>
        <CardContent class="p-4 grid gap-1.5 flex-1 content-start">
            <Label 
                v-for="permission in group.items" 
                :key="permission.id" 
                class="flex items-center space-x-3 group/item rounded-md p-2 hover:bg-accent/50 transition-colors cursor-pointer font-normal"
            >
                <Checkbox 
                    :id="permission.id" 
                    :checked="modelValue.includes(permission.id)"
                    @update:checked="togglePermission(permission.id)"
                    class="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                />
                <span class="text-sm font-medium leading-none flex-1 group-hover/item:text-foreground text-muted-foreground transition-colors">
                    {{ permission.label }}
                </span>
            </Label>
        </CardContent>
    </Card>
</template>
