<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { route } from "ziggy-js";
import { computed, ref } from "vue";
import StateMunicipalityPicker from "../components/StateMunicipalityPicker.vue";
import type { CalculationTypeOption, SupportedStateOption } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    states: SupportedStateOption[];
    types: CalculationTypeOption[];
    presentation: string;
    selected: { state?: string; ibge?: string; name?: string };
}>();

const choice = ref<{ state: string; ibgeCode: number; name: string } | null>(null);

const availableTypes = computed(() => {
    const state = props.states.find((item) => item.value === choice.value?.state);

    return state ? props.types.filter((type) => state.types.includes(type.value)) : [];
});
</script>

<template>
    <Head title="Calculadora de Emolumentos" />

    <div class="mb-20 space-y-8 rounded-lg border border-border bg-card p-6 shadow-sm">
        <div class="space-y-3">
            <h2 class="text-2xl font-semibold tracking-tight text-foreground">Calculadora de Emolumentos</h2>
            <p class="text-sm leading-relaxed text-muted-foreground">{{ presentation }}</p>
        </div>

        <StateMunicipalityPicker
            :states="states"
            :state="selected.state ?? null"
            :ibge-code="selected.ibge ? Number(selected.ibge) : null"
            :labels="false"
            @change="choice = $event"
        />

        <div class="space-y-4 border-t border-border pt-6">
            <h3 class="text-xl font-semibold text-foreground">Escolha o tipo de serviço a calcular</h3>

            <p v-if="!choice" class="text-sm text-muted-foreground">Selecione o estado e o município da comarca.</p>

            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Link
                    v-for="type in availableTypes"
                    :key="type.value"
                    :href="route('tenant.documents.fee-calculator.create', { type: type.value, state: choice.state, ibge: choice.ibgeCode, name: choice.name })"
                    class="block rounded-lg border border-border bg-background p-4 pb-8 shadow-sm transition-colors hover:border-primary hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <p class="text-lg font-semibold tracking-wide text-foreground uppercase">{{ type.label }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">{{ type.summary }}</p>
                </Link>
            </div>
        </div>
    </div>
</template>
