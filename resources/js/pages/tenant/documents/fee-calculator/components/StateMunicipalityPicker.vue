<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
import ComboboxSelect from "@/components/ui/combobox/ComboboxSelect.vue";
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
        /** Rótulos "Estado"/"Município" acima dos campos (a calculadora não usa). */
        labels?: boolean;
    }>(),
    { state: null, ibgeCode: null, disabled: false, labels: true },
);

const emit = defineEmits<{
    (e: "change", value: { state: string; ibgeCode: number; name: string; itbiModule: string | null } | null): void;
}>();

const selectedState = ref<string | null>(props.state);
const selectedCode = ref<number | null>(props.ibgeCode);
const municipalities = ref<MunicipalityOption[]>([]);
const loading = ref(false);
const error = ref("");

const stateOptions = computed(() => props.states.map((state) => ({ value: state.value, label: state.label })));

const municipalityOptions = computed(() =>
    municipalities.value.map((item) => ({
        value: item.ibge_code,
        label: item.has_itbi ? item.name : `${item.name} (sem ITBI cadastrado)`,
    })),
);

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

function onStateChange(value: string | number) {
    if (String(value) === selectedState.value) {
        return;
    }

    selectedState.value = String(value);
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
            <FieldLabel v-if="labels" for="picker_state">Estado *</FieldLabel>
            <ComboboxSelect
                id="picker_state"
                aria-label="Estado"
                :model-value="selectedState"
                :options="stateOptions"
                placeholder="Selecione o estado..."
                search-placeholder="Buscar estado..."
                :disabled="disabled"
                @update:model-value="onStateChange"
            />
        </Field>

        <Field>
            <FieldLabel v-if="labels" for="picker_municipality">Município *</FieldLabel>
            <ComboboxSelect
                id="picker_municipality"
                aria-label="Município"
                :model-value="selectedCode"
                :options="municipalityOptions"
                placeholder="Selecione o município..."
                search-placeholder="Buscar município..."
                no-results-text="Nenhum município encontrado."
                :disabled="disabled || !selectedState || loading"
                :loading="loading"
                @update:model-value="select(Number($event))"
            />
            <FieldDescription v-if="error" class="text-red-600">{{ error }}</FieldDescription>
        </Field>
    </div>
</template>
