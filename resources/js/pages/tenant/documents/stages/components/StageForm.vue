<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
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
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = withDefaults(
    defineProps<{
        form: ReturnType<typeof useForm>;
        submitText?: string;
    }>(),
    {
        submitText: "Salvar Etapa",
    },
);

const emit = defineEmits(["submit"]);

// Sem "has_date" os campos obrigatórios dependentes não fazem sentido.
watch(
    () => props.form.has_date,
    (enabled) => {
        if (!enabled) {
            props.form.date_required = false;
        }
    },
);

// Sem "has_upload" os campos obrigatórios dependentes não fazem sentido.
watch(
    () => props.form.has_upload,
    (enabled) => {
        if (!enabled) {
            props.form.upload_required = false;
            props.form.title_required = false;
        }
    },
);

function onSubmit() {
    emit("submit");
}
</script>

<template>
    <form
        @submit.prevent="onSubmit"
        class="space-y-8 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8"
    >
        <slot name="before" />

        <div class="mb-8">
            <h3 class="mb-6 text-lg font-semibold text-card-foreground">
                Dados da Etapa
            </h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Field>
                    <FieldLabel for="name">Nome *</FieldLabel>
                    <Input id="name" v-model="form.name" placeholder="Ex: Assinatura de Escritura" required />
                    <FieldError v-if="form.errors.name">{{
                        form.errors.name
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="order">Ordem *</FieldLabel>
                    <Input id="order" v-model.number="form.order" type="number" min="0" required />
                    <FieldError v-if="form.errors.order">{{
                        form.errors.order
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="has_date">Possui data? *</FieldLabel>
                    <Select
                        :model-value="form.has_date ? '1' : '0'"
                        @update:model-value="form.has_date = $event === '1'"
                    >
                        <SelectTrigger id="has_date">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.has_date">{{
                        form.errors.has_date
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="date_required">Data obrigatória? *</FieldLabel>
                    <Select
                        :model-value="form.date_required ? '1' : '0'"
                        @update:model-value="form.date_required = $event === '1'"
                        :disabled="!form.has_date"
                    >
                        <SelectTrigger id="date_required">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.date_required">{{
                        form.errors.date_required
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="has_upload">Possui upload de arquivo? *</FieldLabel>
                    <Select
                        :model-value="form.has_upload ? '1' : '0'"
                        @update:model-value="form.has_upload = $event === '1'"
                    >
                        <SelectTrigger id="has_upload">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.has_upload">{{
                        form.errors.has_upload
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="upload_required">Arquivo obrigatório? *</FieldLabel>
                    <Select
                        :model-value="form.upload_required ? '1' : '0'"
                        @update:model-value="form.upload_required = $event === '1'"
                        :disabled="!form.has_upload"
                    >
                        <SelectTrigger id="upload_required">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.upload_required">{{
                        form.errors.upload_required
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="title_required">Título do arquivo obrigatório? *</FieldLabel>
                    <Select
                        :model-value="form.title_required ? '1' : '0'"
                        @update:model-value="form.title_required = $event === '1'"
                        :disabled="!form.has_upload"
                    >
                        <SelectTrigger id="title_required">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.title_required">{{
                        form.errors.title_required
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="notes_required">Observação obrigatória? *</FieldLabel>
                    <Select
                        :model-value="form.notes_required ? '1' : '0'"
                        @update:model-value="form.notes_required = $event === '1'"
                    >
                        <SelectTrigger id="notes_required">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.notes_required">{{
                        form.errors.notes_required
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="completion_deadline_hours">Prazo de conclusão (horas) *</FieldLabel>
                    <Input id="completion_deadline_hours" v-model.number="form.completion_deadline_hours" type="number" min="0" required />
                    <FieldDescription>0 = sem prazo.</FieldDescription>
                    <FieldError v-if="form.errors.completion_deadline_hours">{{
                        form.errors.completion_deadline_hours
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="alert_deadline_hours">Prazo de alerta (horas) *</FieldLabel>
                    <Input id="alert_deadline_hours" v-model.number="form.alert_deadline_hours" type="number" min="0" required />
                    <FieldDescription>0 = sem alerta. Não pode ser maior que o prazo de conclusão.</FieldDescription>
                    <FieldError v-if="form.errors.alert_deadline_hours">{{
                        form.errors.alert_deadline_hours
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="shows_property_data">Exibir dados do imóvel nesta etapa? *</FieldLabel>
                    <Select
                        :model-value="form.shows_property_data ? '1' : '0'"
                        @update:model-value="form.shows_property_data = $event === '1'"
                    >
                        <SelectTrigger id="shows_property_data">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.shows_property_data">{{
                        form.errors.shows_property_data
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="shows_registry_protocol">Exibir protocolo de registro nesta etapa? *</FieldLabel>
                    <Select
                        :model-value="form.shows_registry_protocol ? '1' : '0'"
                        @update:model-value="form.shows_registry_protocol = $event === '1'"
                    >
                        <SelectTrigger id="shows_registry_protocol">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1">Sim</SelectItem>
                                <SelectItem value="0">Não</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FieldError v-if="form.errors.shows_registry_protocol">{{
                        form.errors.shows_registry_protocol
                    }}</FieldError>
                </Field>
            </div>
        </div>

        <div class="flex justify-end border-t border-border pt-6">
            <Button
                type="submit"
                class="text-md w-full px-10 font-bold md:w-auto"
                :loading="form.processing"
                :disabled="form.processing"
            >
                {{ submitText }}
            </Button>
        </div>
    </form>
</template>
