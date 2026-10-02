<script setup lang="ts">
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field";
import FieldError from "@/components/ui/field/FieldError.vue";
import { Input } from "@/components/ui/input";
import { ChevronRight } from "lucide-vue-next";
import AuthLayout from "@/layouts/AuthLayout.vue";

const emailSent = ref<boolean>(false);

const form = useForm({
    email: "",
});

function submit() {
    form.post(route("tenant.password.email"), {
        preserveScroll: true,
        onSuccess: () => {
            emailSent.value = true;
        },
    });
}
</script>

<template>
    <AuthLayout title="Esqueci minha senha">
        <form class="p-6 md:p-8" @submit.prevent="submit" v-if="!emailSent">
            <FieldGroup>
                <div class="flex flex-col items-center gap-2 text-center">
                    <h1 class="text-2xl font-bold">Esqueci minha senha</h1>
                    <p class="text-balance text-muted-foreground">
                        Informe seu e-mail e em breve você receberá um e-mail
                        para redefinir a senha.
                    </p>
                </div>
                <Field>
                    <FieldLabel for="email">Email</FieldLabel>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="m@example.com"
                        required
                    />
                    <FieldError v-if="form.errors.email">{{ form.errors.email }}</FieldError>
                </Field>

                <Field>
                    <Button type="submit" :disabled="form.processing">Enviar</Button>
                </Field>
            </FieldGroup>
        </form>
        <div
            v-else
            class="flex flex-col items-center gap-2 p-6 pb-2 text-center"
        >
            <h1 class="text-2xl font-bold">Link enviado</h1>
            <p class="text-balance text-muted-foreground">
                Se o e-mail estiver cadastrado, você receberá um link com as
                informações para redefinição de senha.
            </p>
            <div
                class="mt-4 flex w-full items-center justify-between gap-2 text-sm text-muted-foreground"
            >
                <span>Problemas ao receber e-mail?</span>
                <Button variant="ghost" :disabled="form.processing" @click="submit">
                    Enviar novamente <ChevronRight />
                </Button>
            </div>
        </div>
    </AuthLayout>
</template>
