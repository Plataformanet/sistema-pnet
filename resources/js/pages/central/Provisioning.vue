<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";

const props = defineProps({
    tenantId: { type: String, required: true },
    loginUrl: { type: String, required: true },
    statusUrl: { type: String, required: true },
});

// pending → provisionando | slow → demorando (ainda ok) | timeout → desistimos
// de esperar (sem declarar falha) | failed → falha REAL vinda do servidor.
const state = ref("pending");

const POLL_INTERVAL = 3000;
// Após isso, só troca a mensagem para "demorando" — NÃO é falha; continua.
const SLOW_AFTER_MS = 90_000; // 1min30
// Limite para parar o polling (evita loop eterno se não houver worker).
const GIVE_UP_AFTER_MS = 15 * 60_000; // 15 min

const startedAt = Date.now();
let timer = null;

async function checkStatus() {
    const elapsed = Date.now() - startedAt;

    try {
        const response = await fetch(props.statusUrl, {
            headers: { Accept: "application/json" },
        });
        const data = await response.json();

        if (data.ready) {
            state.value = "ready";
            stopPolling();
            window.location.href = props.loginUrl;
            return;
        }

        // Falha de verdade só vem do servidor (tenant marcado failed/removido).
        if (data.status === "failed") {
            state.value = "failed";
            stopPolling();
            return;
        }

        // Ainda provisionando: ajusta a mensagem, mas segue aguardando.
        if (elapsed >= GIVE_UP_AFTER_MS) {
            state.value = "timeout";
            stopPolling();
        } else if (elapsed >= SLOW_AFTER_MS) {
            state.value = "slow";
        }
    } catch (error) {
        // Erros transitórios de rede: mantém o polling.
    }
}

function stopPolling() {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
}

function reload() {
    window.location.reload();
}

onMounted(() => {
    checkStatus();
    timer = setInterval(checkStatus, POLL_INTERVAL);
});

onBeforeUnmount(stopPolling);
</script>

<template>
    <div class="ml-1 flex min-h-screen flex-col items-center justify-center gap-4 px-4 text-center">
        <template v-if="state === 'failed'">
            <h1 class="text-lg font-bold text-red-600">
                Não foi possível provisionar sua conta.
            </h1>
            <a href="/cadastro" class="text-blue-600 underline">
                Tentar novamente
            </a>
        </template>

        <template v-else-if="state === 'timeout'">
            <h1 class="text-lg font-bold">Isso está demorando mais que o esperado.</h1>
            <p class="max-w-md text-sm text-gray-500">
                Seu ambiente ainda pode estar sendo preparado. Aguarde alguns
                instantes e recarregue a página para acessar o login.
            </p>
            <button
                type="button"
                class="text-blue-600 underline"
                @click="reload"
            >
                Recarregar
            </button>
        </template>

        <template v-else>
            <div
                class="h-10 w-10 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600"
            ></div>
            <h1 class="text-lg font-bold">Preparando sua conta...</h1>
            <p class="max-w-md text-sm text-gray-500">
                Estamos criando seu ambiente. Você será redirecionado ao login
                automaticamente.
                <template v-if="state === 'slow'">
                    <br />Isso pode levar alguns minutos — pode deixar esta aba
                    aberta.
                </template>
            </p>
        </template>
    </div>
</template>
