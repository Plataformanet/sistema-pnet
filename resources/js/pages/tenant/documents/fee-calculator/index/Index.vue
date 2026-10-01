<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Calculator } from "lucide-vue-next";
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

    <div class="mb-6 border-b border-border pb-4">
        <h2 class="text-3xl font-bold tracking-tight text-foreground">Calculadora de Emolumentos</h2>
        <p class="mt-2 max-w-4xl text-sm text-muted-foreground">{{ presentation }}</p>
    </div>

    <div class="mx-auto mb-20 max-w-6xl space-y-6">
        <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <StateMunicipalityPicker
                :states="states"
                :state="selected.state ?? null"
                :ibge-code="selected.ibge ? Number(selected.ibge) : null"
                @change="choice = $event"
            />
        </div>

        <p v-if="!choice" class="text-center text-muted-foreground">- Selecione o Estado e o município da comarca -</p>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <Card v-for="type in availableTypes" :key="type.value" class="flex flex-col">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2"><Calculator class="h-5 w-5" /> {{ type.label }}</CardTitle>
                    <CardDescription>{{ type.description }}</CardDescription>
                </CardHeader>
                <CardContent class="mt-auto">
                    <Button as-child class="w-full">
                        <Link :href="route('tenant.documents.fee-calculator.create', { type: type.value, state: choice.state, ibge: choice.ibgeCode, name: choice.name })">
                            Calcular
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
