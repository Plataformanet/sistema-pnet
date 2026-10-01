<script setup lang="ts">
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import MoneyInput from "@/components/MoneyInput.vue";
import { formatMoney } from "@/lib/masks";
import { computed, ref, useId } from "vue";
import type { BillableServiceOption, ServiceSelection } from "@/types";

/**
 * Seleção de serviços cobráveis com valor negociado opcional (vazio = preço de
 * tabela). O v-model é `[{ id, amount|null }]`, com valores em centavos.
 */
const props = defineProps<{
    services: BillableServiceOption[];
    modelValue: ServiceSelection[];
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: ServiceSelection[]): void;
}>();

const uid = useId();
const search = ref("");
const editing = ref<number | null>(null);

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    return term ? props.services.filter((service) => service.name.toLowerCase().includes(term)) : props.services;
});

const selected = (id: number) => props.modelValue.find((item) => item.id === id);

function toggle(id: number, checked: boolean) {
    emit("update:modelValue", checked ? [...props.modelValue, { id, amount: null }] : props.modelValue.filter((item) => item.id !== id));
}

function toggleAll(checked: boolean) {
    emit("update:modelValue", checked ? props.services.map((service) => selected(service.id) ?? { id: service.id, amount: null }) : []);
}

function setAmount(id: number, amount: number | null) {
    emit("update:modelValue", props.modelValue.map((item) => (item.id === id ? { ...item, amount } : item)));
}
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <Input v-model="search" placeholder="Buscar serviço..." class="max-w-sm" />
            <div class="flex items-center gap-2">
                <Checkbox
                    :id="`${uid}-all`"
                    :model-value="services.length > 0 && modelValue.length === services.length"
                    @update:model-value="toggleAll($event === true)"
                />
                <label :for="`${uid}-all`" class="cursor-pointer text-sm">Selecionar todos</label>
            </div>
        </div>

        <p v-if="!services.length" class="text-sm text-muted-foreground">Nenhum serviço cadastrado.</p>

        <div v-for="service in filtered" :key="service.id" class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-border p-3">
            <div class="flex items-center gap-2">
                <Checkbox
                    :id="`${uid}-${service.id}`"
                    :model-value="!!selected(service.id)"
                    @update:model-value="toggle(service.id, $event === true)"
                />
                <label :for="`${uid}-${service.id}`" class="cursor-pointer">
                    <span class="font-medium">{{ service.name }}</span>
                    <span class="ml-2 text-sm text-muted-foreground">tabela: {{ formatMoney(service.price) }}</span>
                </label>
            </div>
            <div v-if="selected(service.id)" class="flex items-center gap-2">
                <template v-if="editing === service.id">
                    <MoneyInput
                        :model-value="selected(service.id)?.amount ?? service.price"
                        class="w-36"
                        @update:model-value="setAmount(service.id, $event)"
                    />
                    <Button type="button" size="sm" variant="ghost" @click="editing = null">OK</Button>
                </template>
                <template v-else>
                    <span class="text-sm">{{ formatMoney(selected(service.id)?.amount ?? service.price) }}</span>
                    <Button type="button" size="sm" variant="outline" @click="editing = service.id">Editar valor</Button>
                </template>
            </div>
        </div>
    </div>
</template>
