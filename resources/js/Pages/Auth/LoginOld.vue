<!-- resources/js/Pages/Auth/Login.vue -->
<script setup>
import { usePage } from "@inertiajs/vue3";
import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";

const flash = computed(() => usePage().props.flash);

const form = useForm({
    email: flash.value.email ?? "", // pré-preenche se vier do cadastro
    password: "",
    remember: false,
});

function submit() {
    form.post(route("tenant.login.submit"), {
        onFinish: () => form.reset("password"),
    });
}
</script>

<template>
    <!-- OLD LOGIN-->
    <div>
        <!-- Mensagem de sucesso vinda do cadastro -->
        <div v-if="flash.success" class="alert alert-success">
            {{ flash.success }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <label for="email">E-mail</label>
                <input
                    id="email"
                    v-model="form.email"
                    class="border-1 border-solid"
                    type="email"
                    required
                    autofocus
                />
                <span v-if="form.errors.email">{{ form.errors.email }}</span>
            </div>

            <div>
                <label for="password">Senha</label>
                <input
                    id="password"
                    v-model="form.password"
                    class="border-1 border-solid"
                    type="password"
                    required
                />
                <span v-if="form.errors.password">{{
                    form.errors.password
                }}</span>
            </div>

            <button
                class="mt-5 cursor-pointer rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700 disabled:opacity-50"
                type="submit"
                :disabled="form.processing"
            >
                Entrar
            </button>
        </form>
    </div>
</template>
