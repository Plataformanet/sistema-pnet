<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    CardFooter,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { FileCheck, Sparkles, Home, Building2 } from "lucide-vue-next";

// Dados simulados das últimas propostas assinadas
const approvals = [
    {
        id: 1,
        client: "Carlos Eduardo Santos",
        type: "Financiamento Imobiliário",
        bank: "Itaú",
        value: "R$ 450.000",
        time: "Há 2 horas",
        icon: Home,
        colorClass:
            "bg-orange-100 text-orange-600 dark:bg-orange-500/20 dark:text-orange-400",
    },
    {
        id: 2,
        client: "Mariana Silva (PJ)",
        type: "Crédito com Garantia",
        bank: "Caixa Econômica",
        value: "R$ 120.000",
        time: "Hoje, 09:30",
        icon: Building2,
        colorClass:
            "bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400",
    },
    {
        id: 3,
        client: "Roberto Almeida",
        type: "Consórcio Contemplado",
        bank: "Santander",
        value: "R$ 80.000",
        time: "Ontem, 16:45",
        icon: FileCheck,
        colorClass:
            "bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400",
    },
];
</script>

<template>
    <Card class="col-span-1 flex h-full flex-col md:col-span-2 lg:col-span-3">
        <CardHeader class="flex flex-row items-center justify-between pb-4">
            <div>
                <CardTitle class="flex items-center gap-2 text-lg">
                    Últimas Aprovações
                    <Sparkles class="h-4 w-4 text-amber-500" />
                </CardTitle>
                <CardDescription
                    >Contratos recentes emitidos e assinados.</CardDescription
                >
            </div>
        </CardHeader>

        <CardContent class="flex-1 px-4 sm:px-6">
            <div class="space-y-6">
                <!-- Lista de Aprovações -->
                <div
                    v-for="item in approvals"
                    :key="item.id"
                    class="group flex items-start space-x-3 sm:space-x-4"
                >
                    <!-- Ícone Dinâmico com fundo suave -->
                    <div
                        :class="[
                            item.colorClass,
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition-transform group-hover:scale-110',
                        ]"
                    >
                        <component :is="item.icon" class="h-5 w-5" />
                    </div>

                    <!-- Informações do Cliente -->
                    <div class="flex-1 space-y-1 truncate">
                        <p
                            class="text-sm leading-none font-medium text-foreground"
                        >
                            {{ item.client }}
                        </p>
                        <p
                            class="flex items-center gap-1.5 truncate pt-1 text-xs text-muted-foreground"
                        >
                            {{ item.type }}
                            <span
                                class="inline-block h-1 w-1 rounded-full bg-border"
                            ></span>
                            {{ item.bank }}
                        </p>
                    </div>

                    <!-- Valor e Tempo -->
                    <div class="flex flex-col items-end gap-1 text-right">
                        <span
                            class="text-sm font-semibold tracking-tight text-emerald-600 dark:text-emerald-400"
                        >
                            {{ item.value }}
                        </span>
                        <span class="block text-[10px] text-muted-foreground">
                            {{ item.time }}
                        </span>
                    </div>
                </div>
            </div>
        </CardContent>

        <CardFooter class="border-t pt-4">
            <Button
                variant="outline"
                class="w-full text-foreground/80 transition-colors hover:text-foreground"
            >
                Ver Histórico Completo
            </Button>
        </CardFooter>
    </Card>
</template>
