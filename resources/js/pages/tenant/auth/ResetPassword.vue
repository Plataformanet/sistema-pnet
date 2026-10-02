<script setup lang="ts">
import { Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { Eye, EyeOff } from "lucide-vue-next";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field";
import FieldError from "@/components/ui/field/FieldError.vue";
import { Input } from "@/components/ui/input";
import AuthLayout from "@/layouts/AuthLayout.vue";

const props = defineProps<{
    token: string;
    email: string;
}>();

const showPassword = ref(false);
const showConfirmation = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: "",
    password_confirmation: "",
});

function submit() {
    form.post(route("tenant.password.update"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
}
</script>

<template>
    <AuthLayout title="Recuperar Senha">
        <form class="p-6 md:p-8" @submit.prevent="submit">
            <FieldGroup>
                <div class="flex flex-col items-center gap-2 text-center">
                    <h1 class="text-2xl font-bold">Definir senha</h1>
                    <p class="text-balance text-muted-foreground">
                        Insira sua nova senha
                    </p>
                </div>
                <Field>
                    <FieldLabel for="email">Email</FieldLabel>
                    <Input id="email" v-model="form.email" type="email" readonly />
                    <FieldError v-if="form.errors.email">
                        {{ form.errors.email }}
                        <Link :href="route('tenant.forgot-password')" class="underline">Solicitar novo link</Link>
                    </FieldError>
                    <FieldError v-if="form.errors.token">{{ form.errors.token }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="password">Senha</FieldLabel>
                    <div class="relative">
                        <Input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="********"
                            autocomplete="new-password"
                            class="pr-10"
                            required
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-muted-foreground transition-colors hover:text-foreground focus:outline-none"
                            :title="showPassword ? 'Ocultar senha' : 'Exibir senha'"
                            :aria-label="showPassword ? 'Ocultar senha' : 'Exibir senha'"
                        >
                            <EyeOff v-if="showPassword" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <FieldError v-if="form.errors.password">{{ form.errors.password }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="password_confirmation">Confirmar senha</FieldLabel>
                    <div class="relative">
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="showConfirmation ? 'text' : 'password'"
                            placeholder="********"
                            autocomplete="new-password"
                            class="pr-10"
                            required
                        />
                        <button
                            type="button"
                            @click="showConfirmation = !showConfirmation"
                            class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-muted-foreground transition-colors hover:text-foreground focus:outline-none"
                            :title="showConfirmation ? 'Ocultar senha' : 'Exibir senha'"
                            :aria-label="showConfirmation ? 'Ocultar senha' : 'Exibir senha'"
                        >
                            <EyeOff v-if="showConfirmation" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                </Field>
                <Field>
                    <Button type="submit" :disabled="form.processing">Salvar senha</Button>
                </Field>
            </FieldGroup>
        </form>
    </AuthLayout>
</template>
