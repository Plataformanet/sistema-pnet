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
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import { User, Lock, Upload, Trash2, Loader2, KeyRound, Eye, EyeOff } from "lucide-vue-next";
import { toast } from "vue-sonner";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        photo_url?: string | null;
    };
}>();

// Form de Perfil
const profileForm = useForm({
    name: props.user.name ?? "",
    email: props.user.email ?? "",
    photo: null as File | null,
    remove_photo: false,
});

// Form de Senha
const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const previewAvatar = ref<string | null>(props.user.photo_url ?? null);
const avatarInputRef = ref<HTMLInputElement | null>(null);

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

watch(
    () => props.user.photo_url,
    (newUrl) => {
        if (!profileForm.photo) {
            previewAvatar.value = newUrl ?? null;
        }
    }
);

function getInitials(name: string) {
    if (!name) return "U";
    return name
        .split(" ")
        .map((part) => part[0])
        .slice(0, 2)
        .join("")
        .toUpperCase();
}

function handleAvatarChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        profileForm.photo = file;
        profileForm.remove_photo = false;

        const reader = new FileReader();
        reader.onload = (e) => {
            previewAvatar.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
}

function removeAvatar() {
    profileForm.photo = null;
    profileForm.remove_photo = true;
    previewAvatar.value = null;
    if (avatarInputRef.value) {
        avatarInputRef.value.value = "";
    }
}

function submitProfile() {
    profileForm.post(route("tenant.profile.update"), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            profileForm.photo = null;
            profileForm.remove_photo = false;
            toast.success("Perfil atualizado com sucesso!");
        },
        onError: (errors) => {
            console.error("Erro ao salvar perfil:", errors);
            toast.error("Erro ao atualizar perfil. Verifique os dados.");
        },
    });
}

function submitPassword() {
    passwordForm.put(route("tenant.profile.password.update"), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            toast.success("Senha alterada com sucesso!");
        },
        onError: () => {
            toast.error("Erro ao alterar a senha. Verifique os dados.");
        },
    });
}
</script>

<template>
    <Head title="Meu Perfil" />

    <div class="mb-6 flex flex-col gap-1 border-b border-border pb-4">
        <h2 class="text-3xl font-bold tracking-tight text-foreground flex items-center gap-2">
            Meu Perfil
        </h2>
        <p class="text-sm text-muted-foreground">
            Atualize suas informações pessoais, foto de avatar e credenciais de acesso.
        </p>
    </div>

    <div class="mx-auto max-w-4xl space-y-8 pb-20">
        <!-- Card Informações Pessoais & Avatar -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-lg font-semibold">
                    <User class="h-5 w-5 text-primary" />
                    Informações Pessoais
                </CardTitle>
                <CardDescription>
                    Altere seu nome e foto de perfil. O endereço de e-mail é apenas para leitura.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submitProfile" class="space-y-6">
                    <!-- Seção Avatar -->
                    <div class="flex flex-col sm:flex-row items-center gap-6 pb-2">
                        <Avatar class="h-24 w-24 border-2 border-border shadow-sm">
                            <AvatarImage
                                v-if="previewAvatar"
                                :src="previewAvatar"
                                :alt="profileForm.name"
                            />
                            <AvatarFallback class="text-xl font-bold bg-accent text-accent-foreground">
                                {{ getInitials(profileForm.name) }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="flex flex-col gap-2">
                            <input
                                ref="avatarInputRef"
                                type="file"
                                accept="image/png, image/jpeg, image/webp"
                                class="hidden"
                                @change="handleAvatarChange"
                            />
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="cursor-pointer"
                                    @click="avatarInputRef?.click()"
                                >
                                    <Upload class="mr-2 h-4 w-4" />
                                    Alterar Foto
                                </Button>

                                <Button
                                    v-if="previewAvatar"
                                    type="button"
                                    variant="destructive"
                                    size="sm"
                                    class="cursor-pointer"
                                    @click="removeAvatar"
                                >
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Remover Foto
                                </Button>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Formatos aceitos: PNG, JPG ou WebP de até 2MB.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="profile-name">Nome Completo</Label>
                            <Input
                                id="profile-name"
                                v-model="profileForm.name"
                                placeholder="Seu nome"
                                required
                            />
                            <span
                                v-if="profileForm.errors.name"
                                class="text-xs text-destructive"
                            >
                                {{ profileForm.errors.name }}
                            </span>
                        </div>

                        <div class="space-y-2">
                            <Label for="profile-email">E-mail</Label>
                            <Input
                                id="profile-email"
                                type="email"
                                v-model="profileForm.email"
                                placeholder="seu.email@empresa.com"
                                readonly
                                class="bg-muted cursor-not-allowed text-muted-foreground select-none"
                            />
                            <span
                                v-if="profileForm.errors.email"
                                class="text-xs text-destructive"
                            >
                                {{ profileForm.errors.email }}
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <Button
                            type="submit"
                            class="cursor-pointer"
                            :disabled="profileForm.processing"
                        >
                            <Loader2
                                v-if="profileForm.processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            Salvar Perfil
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <!-- Card Segurança & Alteração de Senha -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-lg font-semibold">
                    <KeyRound class="h-5 w-5 text-primary" />
                    Alterar Senha
                </CardTitle>
                <CardDescription>
                    Certifique-se de que sua conta está usando uma senha forte e segura.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submitPassword" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="current_password">Senha Atual</Label>
                        <div class="relative">
                            <Input
                                id="current_password"
                                :type="showCurrentPassword ? 'text' : 'password'"
                                v-model="passwordForm.current_password"
                                placeholder="Digite sua senha atual"
                                required
                                class="pr-10"
                            />
                            <button
                                type="button"
                                @click="showCurrentPassword = !showCurrentPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-muted-foreground transition-colors hover:text-foreground focus:outline-none"
                                :title="showCurrentPassword ? 'Ocultar senha' : 'Exibir senha'"
                            >
                                <EyeOff v-if="showCurrentPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <span
                            v-if="passwordForm.errors.current_password"
                            class="text-xs text-destructive"
                        >
                            {{ passwordForm.errors.current_password }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="password">Nova Senha</Label>
                            <div class="relative">
                                <Input
                                    id="password"
                                    :type="showNewPassword ? 'text' : 'password'"
                                    v-model="passwordForm.password"
                                    placeholder="Nova senha"
                                    required
                                    class="pr-10"
                                />
                                <button
                                    type="button"
                                    @click="showNewPassword = !showNewPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-muted-foreground transition-colors hover:text-foreground focus:outline-none"
                                    :title="showNewPassword ? 'Ocultar senha' : 'Exibir senha'"
                                >
                                    <EyeOff v-if="showNewPassword" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <span
                                v-if="passwordForm.errors.password"
                                class="text-xs text-destructive"
                            >
                                {{ passwordForm.errors.password }}
                            </span>
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation">Confirmar Nova Senha</Label>
                            <div class="relative">
                                <Input
                                    id="password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    v-model="passwordForm.password_confirmation"
                                    placeholder="Confirme a nova senha"
                                    required
                                    class="pr-10"
                                />
                                <button
                                    type="button"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-muted-foreground transition-colors hover:text-foreground focus:outline-none"
                                    :title="showConfirmPassword ? 'Ocultar senha' : 'Exibir senha'"
                                >
                                    <EyeOff v-if="showConfirmPassword" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <Button
                            type="submit"
                            variant="default"
                            class="cursor-pointer"
                            :disabled="passwordForm.processing"
                        >
                            <Loader2
                                v-if="passwordForm.processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            Atualizar Senha
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
