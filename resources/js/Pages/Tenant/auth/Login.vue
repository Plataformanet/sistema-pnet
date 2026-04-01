<!-- resources/js/Pages/Auth/Login.vue -->
<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { useForm } from "@inertiajs/vue3";
import { computed, HTMLAttributes } from "vue";
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
                    <p class="text-muted-foreground text-balance">
                        Faça o login da sua conta
                    </p>
                </div>
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
                    <Input
                        id="password"
                        type="password"
                        required
                        v-model="form.password"
                    />
                    <FieldError v-if="form.errors.password">{{
                        form.errors.password
                    }}</FieldError>
                    <FieldError v-if="$page.props.errors.invalidLogin">{{
                        $page.props.errors.invalidLogin
                    }}</FieldError>
                </Field>
                <Field>
                    <Button type="submit"> Login </Button>
                </Field>

                <!-- <FieldDescription class="text-center">
                    Não possui uma conta?
                    <Link :href="route('tenant.signup')"> Cadastre-se </Link>
                </FieldDescription> -->
            </FieldGroup>
        </form>
    </AuthLayout>
</template>
