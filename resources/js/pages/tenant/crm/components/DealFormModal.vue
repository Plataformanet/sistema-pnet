<script setup lang="ts">
import { ref } from "vue";
import { X, UserPlus, Check, Building2, User, Phone, Mail, FileText } from "lucide-vue-next";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import type { CrmPipeline, CrmStage } from "@/types";

const props = defineProps<{
    isOpen: boolean;
    pipelines: CrmPipeline[];
    stages: CrmStage[];
}>();

const emit = defineEmits<{
    (e: "update:isOpen", value: boolean): void;
    (e: "save", dealData: any): void;
}>();

// Dados da Proposta
const title = ref("");
const contactName = ref("");
const totalValue = ref("");
const selectedPipeline = ref(String(props.pipelines[0]?.id || 1));
const selectedStage = ref(String(props.stages[0]?.id || 101));
const priority = ref("media");

// Modal de Cadastro Rápido de Cliente
const showQuickClientModal = ref(false);
const quickClientType = ref<"F" | "J">("J");
const quickClientName = ref("");
const quickClientCpfCnpj = ref("");
const quickClientPhone = ref("");
const quickClientEmail = ref("");

const handleSaveQuickClient = () => {
    if (!quickClientName.value.trim()) return;

    // Auto preenche o cliente selecionado na proposta
    contactName.value = quickClientName.value;

    // Reset formulário rápido
    showQuickClientModal.value = false;
    quickClientName.value = "";
    quickClientCpfCnpj.value = "";
    quickClientPhone.value = "";
    quickClientEmail.value = "";
};

const handleSave = () => {
    if (!title.value || !contactName.value) return;

    emit("save", {
        title: title.value,
        contact_name: contactName.value,
        total_value: Number(totalValue.value || 0) * 100, // converte reais em centavos
        crm_pipeline_id: Number(selectedPipeline.value),
        crm_stage_id: Number(selectedStage.value),
        priority: priority.value,
    });

    // Reset
    title.value = "";
    contactName.value = "";
    totalValue.value = "";
    emit("update:isOpen", false);
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
        >
            <div
                class="w-full max-w-lg rounded-xl border border-border bg-card p-6 shadow-xl space-y-4 animate-in fade-in-0 zoom-in-95"
            >
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h3 class="text-lg font-bold text-foreground">
                        Nova Proposta Comercial
                    </h3>
                    <button
                        @click="emit('update:isOpen', false)"
                        class="text-muted-foreground hover:text-foreground cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-4 py-1">
                    <div class="space-y-1.5">
                        <Label for="title" class="text-xs font-semibold text-foreground">
                            Título da Proposta *
                        </Label>
                        <Input
                            id="title"
                            v-model="title"
                            placeholder="Ex: Proposta de Implantação de Sistema ERP"
                            required
                        />
                    </div>

                    <!-- Campo Cliente com Atalho de Cadastro Rápido -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="contact" class="text-xs font-semibold text-foreground">
                                Cliente / Empresa *
                            </Label>
                            <button
                                type="button"
                                @click="showQuickClientModal = true"
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-primary hover:underline cursor-pointer"
                            >
                                <UserPlus class="h-3 w-3" /> + Cadastro Rápido
                            </button>
                        </div>
                        <div class="relative">
                            <Input
                                id="contact"
                                v-model="contactName"
                                placeholder="Buscar ou digitar nome do cliente..."
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="value" class="text-xs font-semibold text-foreground">
                                Valor Estimado (R$)
                            </Label>
                            <Input
                                id="value"
                                type="number"
                                v-model="totalValue"
                                placeholder="0,00"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold text-foreground">
                                Prioridade
                            </Label>
                            <Select v-model="priority">
                                <SelectTrigger class="bg-background">
                                    <SelectValue placeholder="Selecione..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="baixa">Baixa</SelectItem>
                                    <SelectItem value="media">Média</SelectItem>
                                    <SelectItem value="alta">Alta</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold text-foreground">
                                Funil de Vendas
                            </Label>
                            <Select v-model="selectedPipeline">
                                <SelectTrigger class="bg-background">
                                    <SelectValue placeholder="Funil..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="p in pipelines"
                                        :key="p.id"
                                        :value="String(p.id)"
                                    >
                                        {{ p.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold text-foreground">
                                Estágio Inicial
                            </Label>
                            <Select v-model="selectedStage">
                                <SelectTrigger class="bg-background">
                                    <SelectValue placeholder="Estágio..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="s in stages"
                                        :key="s.id"
                                        :value="String(s.id)"
                                    >
                                        {{ s.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-border pt-3">
                    <Button
                        type="button"
                        variant="ghost"
                        @click="emit('update:isOpen', false)"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        class="font-semibold cursor-pointer"
                        @click="handleSave"
                    >
                        Criar Proposta
                    </Button>
                </div>
            </div>
        </div>

        <!-- Sub-Modal: Cadastro Rápido de Cliente -->
        <div
            v-if="showQuickClientModal"
            class="fixed inset-0 z-60 flex items-center justify-center bg-black/70 backdrop-blur-xs p-4"
        >
            <div
                class="w-full max-w-md rounded-xl border border-border bg-card p-6 shadow-2xl space-y-4 animate-in fade-in-0 zoom-in-95"
            >
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <div class="flex items-center gap-2">
                        <UserPlus class="h-5 w-5 text-primary" />
                        <h4 class="text-base font-bold text-foreground">
                            Cadastro Rápido de Cliente
                        </h4>
                    </div>
                    <button
                        @click="showQuickClientModal = false"
                        class="text-muted-foreground hover:text-foreground cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-3.5">
                    <!-- Tipo de Pessoa -->
                    <div class="flex items-center gap-2 bg-muted p-1 rounded-lg">
                        <button
                            type="button"
                            @click="quickClientType = 'J'"
                            class="flex-1 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer flex items-center justify-center gap-1.5"
                            :class="quickClientType === 'J' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground'"
                        >
                            <Building2 class="h-3.5 w-3.5" /> Pessoa Jurídica (CNPJ)
                        </button>
                        <button
                            type="button"
                            @click="quickClientType = 'F'"
                            class="flex-1 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer flex items-center justify-center gap-1.5"
                            :class="quickClientType === 'F' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground'"
                        >
                            <User class="h-3.5 w-3.5" /> Pessoa Física (CPF)
                        </button>
                    </div>

                    <!-- Nome / Razão Social -->
                    <div class="space-y-1">
                        <Label class="text-xs font-semibold text-foreground">
                            {{ quickClientType === 'J' ? 'Razão Social / Nome Fantasia *' : 'Nome Completo *' }}
                        </Label>
                        <Input
                            v-model="quickClientName"
                            :placeholder="quickClientType === 'J' ? 'Ex: Construtora Silva Ltda' : 'Ex: João da Silva'"
                            required
                        />
                    </div>

                    <!-- CPF / CNPJ -->
                    <div class="space-y-1">
                        <Label class="text-xs font-semibold text-foreground">
                            {{ quickClientType === 'J' ? 'CNPJ' : 'CPF' }}
                        </Label>
                        <Input
                            v-model="quickClientCpfCnpj"
                            :placeholder="quickClientType === 'J' ? '00.000.000/0001-00' : '000.000.000-00'"
                        />
                    </div>

                    <!-- Contatos Rápidos (Telefone e E-mail) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <Label class="text-xs font-semibold text-foreground">Telefone / WhatsApp</Label>
                            <Input v-model="quickClientPhone" placeholder="(11) 99999-9999" />
                        </div>
                        <div class="space-y-1">
                            <Label class="text-xs font-semibold text-foreground">E-mail</Label>
                            <Input v-model="quickClientEmail" type="email" placeholder="contato@empresa.com" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-border pt-3">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="showQuickClientModal = false"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        class="font-semibold cursor-pointer"
                        @click="handleSaveQuickClient"
                    >
                        <Check class="mr-1 h-3.5 w-3.5" /> Confirmar e Selecionar
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
