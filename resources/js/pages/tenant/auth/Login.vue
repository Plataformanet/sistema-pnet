<!-- resources/js/Pages/Auth/Login.vue -->
<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { useForm } from "@inertiajs/vue3";
import { computed, HTMLAttributes, ref } from "vue";
import { Eye, EyeOff } from "lucide-vue-next";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
    Field,
    FieldDescription,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import AuthLayout from "@/layouts/AuthLayout.vue";
import { route } from "ziggy-js";
import FieldError from "@/components/ui/field/FieldError.vue";

const props = defineProps<{
    class?: HTMLAttributes["class"];
}>();

const flash: any = computed(() => usePage().props.flash);

const showPassword = ref(false);

const form = useForm({
    email: flash.value.email ?? "", // pré-preenche se vier do cadastro
    password: "",
    remember: false,
});

function submit() {
    form.post(route("tenant.login.submit"), {
        // onFinish: () => form.reset("password"),
    });
}
</script>

<template>
    <AuthLayout title="Login">
        <form class="p-6 md:p-8" @submit.prevent="submit">
            <FieldGroup>
                <div class="flex flex-col items-center gap-2 text-center">
                    <h1 class="text-2xl font-bold">Bem vindo de volta</h1>
                    <p class="text-balance text-muted-foreground">
                        Faça o login da sua conta
                    </p>
                </div>
                <Field v-if="flash.error">
                    <FieldError>{{ flash.error }}</FieldError>
                </Field>
                <Field>
                    <FieldLabel for="email"> Email </FieldLabel>
                    <Input
                        id="email"
                        type="email"
                        placeholder="m@example.com"
                        required
                        v-model="form.email"
                    />
                    <FieldError v-if="form.errors.email">{{
                        form.errors.email
                    }}</FieldError>
                </Field>
                <Field>
                    <div class="flex items-center">
                        <FieldLabel for="password"> Senha </FieldLabel>
                        <Link
                            :href="route('tenant.forgot-password')"
                            class="ml-auto text-sm underline-offset-2 hover:underline"
                        >
                            Esqueceu sua senha?
                        </Link>
                    </div>
                    <div class="relative">
                        <Input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            v-model="form.password"
                            class="pr-10"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-muted-foreground transition-colors hover:text-foreground focus:outline-none"
                            :title="showPassword ? 'Ocultar senha' : 'Exibir senha'"
                        >
                            <EyeOff v-if="showPassword" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <FieldError v-if="form.errors.password">{{
                        form.errors.password
                    }}</FieldError>
                    <FieldError v-if="$page.props.errors.invalidLogin">{{
                        $page.props.errors.invalidLogin
                    }}</FieldError>
                </Field>
                <Field>
                    <Button type="submit" :loading="form.processing" :disabled="form.processing">Login</Button>
                </Field>

                <!-- <FieldDescription class="text-center">
                    Não possui uma conta?
                    <Link :href="route('tenant.signup')"> Cadastre-se </Link>
                </FieldDescription> -->
            </FieldGroup>
        </form>
    </AuthLayout>
</template>
