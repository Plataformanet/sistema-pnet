<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Loader2 } from "lucide-vue-next";
import axios from "axios";
import { computed, onMounted, ref } from "vue";
import { route } from "ziggy-js";
import type { MunicipalityOption, SupportedStateOption } from "@/types";

/**
 * UF suportada + município do IBGE. Ao trocar a UF carrega os municípios uma
 * única vez e pré-seleciona a capital.
 */
const props = withDefaults(
    defineProps<{
        states: SupportedStateOption[];
        state?: string | null;
        ibgeCode?: number | null;
        disabled?: boolean;
    }>(),
    { state: null, ibgeCode: null, disabled: false },
);

const emit = defineEmits<{
    (e: "change", value: { state: string; ibgeCode: number; name: string; itbiModule: string | null } | null): void;
}>();

const selectedState = ref<string | null>(props.state);
const selectedCode = ref<number | null>(props.ibgeCode);
const municipalities = ref<MunicipalityOption[]>([]);
const loading = ref(false);
const error = ref("");
const search = ref("");

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    return term ? municipalities.value.filter((item) => item.name.toLowerCase().includes(term)) : municipalities.value;
});

async function loadMunicipalities(state: string, preferredCode: number | null) {
    loading.value = true;
    error.value = "";
    municipalities.value = [];

    try {
        const { data } = await axios.get<MunicipalityOption[]>(route("tenant.documents.fee-calculator.municipalities", state));
        municipalities.value = data;

        const capital = props.states.find((item) => item.value === state)?.capital;
        const preferred =
            data.find((item) => item.ibge_code === preferredCode) ?? data.find((item) => item.name === capital) ?? null;

        select(preferred?.ibge_code ?? null);
    } catch (exception: any) {
        error.value = exception?.response?.data?.message ?? "Não foi possível carregar os municípios.";
    } finally {
        loading.value = false;
    }
}

function onStateChange(value: unknown) {
    selectedState.value = String(value);
    search.value = "";
    loadMunicipalities(String(value), null);
}

function select(code: number | null) {
    selectedCode.value = code;
    const municipality = municipalities.value.find((item) => item.ibge_code === code);

    emit(
        "change",
        municipality && selectedState.value
            ? { state: selectedState.value, ibgeCode: municipality.ibge_code, name: municipality.name, itbiModule: municipality.itbi_module }
            : null,
    );
}

onMounted(() => {
    if (selectedState.value) {
        loadMunicipalities(selectedState.value, selectedCode.value);
    }
});
</script>

<template>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <Field>
            <FieldLabel for="picker_state">Estado *</FieldLabel>
            <Select :model-value="selectedState ?? ''" @update:model-value="onStateChange" :disabled="disabled">
                <SelectTrigger id="picker_state">
                    <SelectValue placeholder="Selecione o estado..." />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem v-for="state in states" :key="state.value" :value="state.value">{{ state.label }}</SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
        </Field>

        <Field>
            <FieldLabel for="picker_municipality">Município *</FieldLabel>
            <div class="flex items-center gap-2">
                <Select
                    :model-value="selectedCode ? String(selectedCode) : ''"
                    @update:model-value="select(Number($event))"
                    :disabled="disabled || !selectedState || loading"
                >
                    <SelectTrigger id="picker_municipality">
                        <SelectValue placeholder="Selecione o município..." />
                    </SelectTrigger>
                    <SelectContent>
                        <div class="p-1">
                            <Input v-model="search" placeholder="Filtrar município..." class="h-8" @keydown.stop />
                        </div>
                        <SelectGroup>
                            <SelectItem v-for="municipality in filtered" :key="municipality.ibge_code" :value="String(municipality.ibge_code)">
                                {{ municipality.name }}{{ municipality.has_itbi ? "" : " (sem ITBI cadastrado)" }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <Loader2 v-if="loading" class="h-4 w-4 animate-spin text-muted-foreground" />
            </div>
            <FieldDescription v-if="error" class="text-red-600">{{ error }}</FieldDescription>
        </Field>
    </div>
</template>
