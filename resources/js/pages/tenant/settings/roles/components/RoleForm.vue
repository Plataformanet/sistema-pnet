<script setup lang="ts">
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import PermissionsGrid from "@/pages/tenant/settings/components/PermissionsGrid.vue";
import { Permission, Role } from "@/types";
import { useForm } from "@inertiajs/vue3";

const props = defineProps<{
    form: ReturnType<typeof useForm>;
    permissions: Permission[];
    role?: Role;
    submitText?: string;
}>();

const emit = defineEmits(["submit"]);
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

            <PermissionsGrid
                v-model="form.permissions"
                :permissions="permissions"
                title="Permissões de Acesso"
            />

            <div class="mt-6 flex justify-end gap-4">
                <Button
                    type="button"
                    variant="outline"
                    @click="() => form.reset()"
                    >Limpar</Button
                >
                <Button
                    type="submit"
                    :loading="form.processing"
                    :disabled="form.processing"
                    >Salvar Cargo</Button
                >
            </div>
        </div>
    </form>
</template>
