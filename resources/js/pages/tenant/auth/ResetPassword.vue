<script setup lang="ts">
import { Button } from "@/components/ui/button";
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import AuthLayout from "@/layouts/AuthLayout.vue";
</script>

<template>
    <AuthLayout title="Recuperar Senha">
        <form class="p-6 md:p-8" @submit="">
            <FieldGroup>
                <div class="flex flex-col items-center gap-2 text-center">
                    <h1 class="text-2xl font-bold">Redefinir senha</h1>
                    <p class="text-balance text-muted-foreground">
                        Insira sua nova senha
                    </p>
                </div>
                <Field>
                    <FieldLabel for="password">Senha</FieldLabel>
                    <Input
                        id="password"
                        type="password"
                        placeholder="********"
                        required
                    />
                </Field>
                <Field>
                    <FieldLabel for="confirmPassword"
                        >Confirmar senha</FieldLabel
                    >
                    <Input
                        id="confirmPassword"
                        type="password"
                        placeholder="********"
                        required
                    />
                </Field>
                <Field>
                    <Button type="submit">Redefinir</Button>
                </Field>
            </FieldGroup>
        </form>
    </AuthLayout>
</template>
