<script setup>
import { useForm } from "@inertiajs/vue3";

const form = useForm({
    name: "",
    domain: "",
    plan_id: "",
    userName: "",
    email: "",
    password: "",
});

function submit() {
    form.post(route("cadastro.store"));
}
</script>

<template>
    <!-- <div v-if="page.flash.toast" class="toast">
        {{ page.flash.toast.message }}
    </div> -->

    <form @submit.prevent="submit" class="ml-1">
        <div class="w-50">
            <label for="name">Empresa:</label>
            <input
                v-model="form.name"
                class="border-1 border-solid"
                type="text"
                id="name"
            />
            <span v-if="form.errors.name" class="text-sm text-red-500">
                {{ form.errors.name }}
            </span>
        </div>

        <div class="w-50">
            <label for="domain">Domínio:</label>
            <input
                v-model="form.domain"
                class="border-1 border-solid"
                type="text"
                id="domain"
            />
            <span v-if="form.errors.domain" class="text-sm text-red-500">
                {{ form.errors.domain }}
            </span>
        </div>

        <div class="w-50">
            <label for="domain">Plano:</label>
            <select v-model="form.plan_id" class="border-1 border-solid" id="plan">
                <option value="">Selecione um plano</option>
                <option value="1">Plano Básico</option>
                <option value="2">Plano Padrão</option>
                <option value="3">Plano Premium</option>
            </select>
            <span v-if="form.errors.plan_id" class="text-sm text-red-500">
                {{ form.errors.plan_id }}
            </span>
        </div>

        <div class="w-50">
            <label for="userName">Nome:</label>
            <input
                v-model="form.userName"
                class="border-1 border-solid"
                type="text"
                id="userName"
            />
            <span v-if="form.errors.userName" class="text-sm text-red-500">
                {{ form.errors.userName }}
            </span>
        </div>

        <div class="w-50">
            <label for="email">Email:</label>
            <input
                v-model="form.email"
                class="border-1 border-solid"
                type="email"
                id="email"
            />
            <span v-if="form.errors.email" class="text-sm text-red-500">
                {{ form.errors.email }}
            </span>
        </div>

        <div class="w-50">
            <label for="password">Senha:</label>
            <input
                v-model="form.password"
                class="border-1 border-solid"
                type="password"
                id="password"
            />
            <span v-if="form.errors.password" class="text-sm text-red-500">
                {{ form.errors.password }}
            </span>
        </div>

        <button
            type="submit"
            :disabled="form.processing"
            class="mt-5 cursor-pointer rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700 disabled:opacity-50"
        >
            {{ form.processing ? "Registrando..." : "Registrar" }}
        </button>
    </form>
</template>
