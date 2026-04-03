<script setup lang="ts">
import { ref, type HTMLAttributes } from "vue";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { ChevronRight } from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";
import AuthLayout from "@/layouts/AuthLayout.vue";

const props = defineProps<{
    class?: HTMLAttributes["class"];
}>();

const emailSent = ref<boolean>(false);

function onEmailSubmit(e: SubmitEvent) {
    e.preventDefault();
    emailSent.value = true;
}
</script>

<template>
    <AuthLayout title="Esqueci minha senha">
        <form class="p-6 md:p-8" @submit="onEmailSubmit" v-if="!emailSent">
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
                        type="email"
                        placeholder="m@example.com"
                        required
                    />
                </Field>

                <Field>
                    <Button type="submit">Enviar</Button>
                </Field>
            </FieldGroup>
        </form>
        <div
            v-else
            class="flex flex-col items-center gap-2 p-6 pb-2 text-center"
        >
            <h1 class="text-2xl font-bold">Link enviado</h1>
            <p class="text-balance text-muted-foreground">
                Você receberá um link no seu e-mail com as informações para
                redefinição de senha.
            </p>
            <div
                class="mt-4 flex w-full items-center justify-between gap-2 text-sm text-muted-foreground"
            >
                <span>Problemas ao receber e-mail?</span>
                <Button variant="ghost">
                    Enviar novamente <ChevronRight />
                </Button>
            </div>
        </div>
    </AuthLayout>
</template>
