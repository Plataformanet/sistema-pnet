<script setup lang="ts">
import { Field, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import FieldError from "@/components/ui/field/FieldError.vue";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { router, usePage } from "@inertiajs/vue3";
import { computed, reactive } from "vue";
import type { FinishedProposalsFilters, ReportDevelopmentOption } from "@/types";

const props = defineProps<{
    url: string;
    filters: FinishedProposalsFilters;
    developments: ReportDevelopmentOption[];
}>();

const page = usePage();
const errors = computed(() => (page.props.errors ?? {}) as Record<string, string>);

const state = reactive({
    start_date: props.filters.start_date ?? "",
    end_date: props.filters.end_date ?? "",
    development_id: props.filters.development_id ? String(props.filters.development_id) : "all",
});

/**
 * Os filtros vão na query string: a URL do relatório filtrado pode ser
 * recarregada ou compartilhada.
 */
function apply() {
    router.get(
        props.url,
        {
            start_date: state.start_date || undefined,
            end_date: state.end_date || undefined,
            development_id: state.development_id === "all" ? undefined : state.development_id,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function clear() {
    state.start_date = "";
    state.end_date = "";
    state.development_id = "all";
    apply();
}
</script>

<template>
    <form
        class="grid grid-cols-1 items-end gap-4 rounded-lg border border-border bg-card p-4 shadow-sm md:grid-cols-4"
        @submit.prevent="apply"
    >
        <Field>
            <FieldLabel for="start_date">Data inicial</FieldLabel>
            <Input id="start_date" v-model="state.start_date" type="date" />
            <FieldError v-if="errors.start_date">{{ errors.start_date }}</FieldError>
        </Field>
        <Field>
            <FieldLabel for="end_date">Data final</FieldLabel>
            <Input id="end_date" v-model="state.end_date" type="date" :min="state.start_date || undefined" />
            <FieldError v-if="errors.end_date">{{ errors.end_date }}</FieldError>
        </Field>
        <Field>
            <FieldLabel for="development_id">Empreendimento</FieldLabel>
            <Select v-model="state.development_id">
                <SelectTrigger id="development_id">
                    <SelectValue placeholder="Todos os empreendimentos" />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem value="all">Todos os empreendimentos</SelectItem>
                        <SelectItem
                            v-for="development in developments"
                            :key="development.id"
                            :value="String(development.id)"
                        >
                            {{ development.name }}{{ development.deleted_at ? " (excluído)" : "" }}
                        </SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
            <FieldError v-if="errors.development_id">{{ errors.development_id }}</FieldError>
        </Field>
        <div class="flex gap-2">
            <Button type="submit" class="flex-1">Filtrar</Button>
            <Button type="button" variant="outline" @click="clear">Limpar</Button>
        </div>
    </form>
</template>
