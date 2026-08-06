<script setup lang="ts">
import { ref, watch } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Building2,
    Upload,
    Trash2,
    MapPin,
    Building,
    Search,
    Loader2,
} from "lucide-vue-next";
import { toast } from "vue-sonner";
import { useCepLookup } from "@/composables/useCepLookup";
import { maskCNPJ, maskPhone, maskCEP, handleMask } from "@/lib/masks";
import { UFS_LIST } from "@/lib/constants";

defineOptions({ layout: TenantLayout });

const ufs = UFS_LIST;

const props = defineProps<{
    company: {
        name?: string;
        trade_name?: string;
        cnpj?: string;
        ie?: string;
        email?: string;
        phone?: string;
        zip_code?: string;
        street?: string;
        number?: string;
        complement?: string;
        neighborhood?: string;
        city?: string;
        state?: string;
    };
    logoUrl?: string | null;
}>();

const form = useForm({
    name: props.company.name ?? "",
    trade_name: props.company.trade_name ?? "",
    cnpj: props.company.cnpj ? maskCNPJ(props.company.cnpj) : "",
    ie: props.company.ie ?? "",
    email: props.company.email ?? "",
    phone: props.company.phone ? maskPhone(props.company.phone) : "",
    zip_code: props.company.zip_code ? maskCEP(props.company.zip_code) : "",
    street: props.company.street ?? "",
    number: props.company.number ?? "",
    complement: props.company.complement ?? "",
    neighborhood: props.company.neighborhood ?? "",
    city: props.company.city ?? "",
    state: props.company.state ?? "",
    logo: null as File | null,
    remove_logo: false,
});

const previewLogo = ref<string | null>(props.logoUrl ?? null);
const fileInputRef = ref<HTMLInputElement | null>(null);

// Reutiliza o composable padrão de busca automática de CEP do módulo de cadastros
const { isLoadingCep } = useCepLookup(form);

watch(
    () => props.logoUrl,
    (newUrl) => {
        if (!form.logo) {
            previewLogo.value = newUrl ?? null;
        }
    }
);

function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.logo = file;
        form.remove_logo = false;

        const reader = new FileReader();
        reader.onload = (e) => {
            previewLogo.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
}

function removeLogo() {
    form.logo = null;
    form.remove_logo = true;
    previewLogo.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = "";
    }
}

function submit() {
    form.post(route("tenant.settings.company.update"), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.logo = null;
            form.remove_logo = false;
            toast.success("Configurações da empresa salvas com sucesso!");
        },
        onError: (errors) => {
            console.error("Erro ao salvar configurações:", errors);
            toast.error("Ocorreu um erro ao salvar as configurações. Verifique os campos.");
        },
    });
}
</script>

<template>
    <Head title="Configurações da Empresa" />

    <div class="mb-6 flex flex-col gap-1 border-b border-border pb-4">
        <h2 class="text-3xl font-bold tracking-tight text-foreground flex items-center gap-2">
            Configurações da Empresa
        </h2>
        <p class="text-sm text-muted-foreground">
            Gerencie os dados cadastrais, endereço e logotipo oficial da sua empresa.
        </p>
    </div>

    <form @submit.prevent="submit" class="mx-auto max-w-5xl space-y-6 pb-20">
        <!-- Logotipo da Empresa -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-lg font-semibold">
                    <Upload class="h-5 w-5 text-primary" />
                    Logotipo da Empresa
                </CardTitle>
                <CardDescription>
                    Envie o logotipo oficial da empresa para exibição no sistema, relatórios e telas (PNG, JPG, WebP ou SVG de até 2MB).
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <div
                        class="relative flex h-32 w-48 items-center justify-center rounded-lg border-2 border-dashed border-border bg-muted/30 p-2 overflow-hidden"
                    >
                        <img
                            v-if="previewLogo"
                            :src="previewLogo"
                            alt="Logotipo da empresa"
                            class="max-h-full max-w-full object-contain"
                        />
                        <div v-else class="text-center text-muted-foreground">
                            <Building class="mx-auto h-8 w-8 opacity-40" />
                            <span class="mt-1 block text-xs">Sem logotipo</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept="image/png, image/jpeg, image/webp, image/svg+xml"
                            class="hidden"
                            @change="handleFileChange"
                        />
                        <div class="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                class="cursor-pointer"
                                @click="fileInputRef?.click()"
                            >
                                <Upload class="mr-2 h-4 w-4" />
                                Selecionar Imagem
                            </Button>

                            <Button
                                v-if="previewLogo"
                                type="button"
                                variant="destructive"
                                class="cursor-pointer"
                                @click="removeLogo"
                            >
                                <Trash2 class="mr-2 h-4 w-4" />
                                Remover
                            </Button>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Recomendado: Imagem retangular ou quadrada com fundo transparente.
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Informações Cadastrais -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-lg font-semibold">
                    <Building2 class="h-5 w-5 text-primary" />
                    Identificação & Contato
                </CardTitle>
                <CardDescription>
                    Informações jurídicas e canais de contato da empresa.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="name">Razão Social</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="Ex: Empresa Exemplo S.A."
                    />
                    <span v-if="form.errors.name" class="text-xs text-destructive">
                        {{ form.errors.name }}
                    </span>
                </div>

                <div class="space-y-2">
                    <Label for="trade_name">Nome Fantasia</Label>
                    <Input
                        id="trade_name"
                        v-model="form.trade_name"
                        placeholder="Ex: Empresa Exemplo"
                    />
                    <span v-if="form.errors.trade_name" class="text-xs text-destructive">
                        {{ form.errors.trade_name }}
                    </span>
                </div>

                <div class="space-y-2">
                    <Label for="cnpj">CNPJ</Label>
                    <Input
                        id="cnpj"
                        v-model="form.cnpj"
                        placeholder="00.000.000/0000-00"
                        @input="handleMask($event, maskCNPJ, val => form.cnpj = val)"
                    />
                    <span v-if="form.errors.cnpj" class="text-xs text-destructive">
                        {{ form.errors.cnpj }}
                    </span>
                </div>

                <div class="space-y-2">
                    <Label for="ie">Inscrição Estadual</Label>
                    <Input
                        id="ie"
                        v-model="form.ie"
                        placeholder="Isento ou nº da inscrição"
                    />
                    <span v-if="form.errors.ie" class="text-xs text-destructive">
                        {{ form.errors.ie }}
                    </span>
                </div>

                <div class="space-y-2">
                    <Label for="email">E-mail Principal</Label>
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        placeholder="contato@empresa.com.br"
                    />
                    <span v-if="form.errors.email" class="text-xs text-destructive">
                        {{ form.errors.email }}
                    </span>
                </div>

                <div class="space-y-2">
                    <Label for="phone">Telefone / Celular</Label>
                    <Input
                        id="phone"
                        v-model="form.phone"
                        placeholder="(00) 00000-0000"
                        @input="handleMask($event, maskPhone, val => form.phone = val)"
                    />
                    <span v-if="form.errors.phone" class="text-xs text-destructive">
                        {{ form.errors.phone }}
                    </span>
                </div>
            </CardContent>
        </Card>

        <!-- Endereço -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-lg font-semibold">
                    <MapPin class="h-5 w-5 text-primary" />
                    Endereço Comercial
                </CardTitle>
                <CardDescription>
                    Endereço da sede da empresa. Digite o CEP para preenchimento automático via ViaCEP.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-2">
                    <Label for="zip_code">CEP</Label>
                    <div class="relative flex items-center">
                        <Input
                            id="zip_code"
                            v-model="form.zip_code"
                            placeholder="00000-000"
                            @input="handleMask($event, maskCEP, val => form.zip_code = val)"
                        />
                        <div
                            class="absolute right-3 text-muted-foreground"
                            v-if="isLoadingCep"
                        >
                            <Loader2 class="h-4 w-4 animate-spin text-primary" />
                        </div>
                        <div
                            class="absolute right-3 text-muted-foreground opacity-50"
                            v-else
                        >
                            <Search class="h-4 w-4" />
                        </div>
                    </div>
                    <span v-if="form.errors.zip_code" class="text-xs text-destructive">
                        {{ form.errors.zip_code }}
                    </span>
                </div>

                <div class="space-y-2 md:col-span-2">
                    <Label for="street">Logradouro / Rua</Label>
                    <Input
                        id="street"
                        v-model="form.street"
                        placeholder="Rua, Avenida, etc."
                    />
                </div>

                <div class="space-y-2">
                    <Label for="number">Número</Label>
                    <Input
                        id="number"
                        v-model="form.number"
                        placeholder="123"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="complement">Complemento</Label>
                    <Input
                        id="complement"
                        v-model="form.complement"
                        placeholder="Sala 101, Bloco A"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="neighborhood">Bairro</Label>
                    <Input
                        id="neighborhood"
                        v-model="form.neighborhood"
                        placeholder="Centro"
                    />
                </div>

                <div class="space-y-2 md:col-span-2">
                    <Label for="city">Cidade</Label>
                    <Input
                        id="city"
                        v-model="form.city"
                        placeholder="São Paulo"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="state">UF (Estado)</Label>
                    <Select v-model="form.state">
                        <SelectTrigger id="state">
                            <SelectValue placeholder="UF" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="uf in ufs"
                                :key="uf"
                                :value="uf"
                            >
                                {{ uf }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </CardContent>
        </Card>

        <!-- Botão Salvar -->
        <div class="flex justify-end gap-3">
            <Button
                type="submit"
                size="lg"
                class="cursor-pointer"
                :disabled="form.processing"
            >
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                Salvar Alterações
            </Button>
        </div>
    </form>
</template>
