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
import { Trophy, Star, ArrowRight } from "lucide-vue-next";

// Dados simulados da equipe
const team = [
    {
        name: "Ana Souza",
        avatar: "AS",
        role: "Atendimento N2",
        issuesResolved: 145,
        rating: "4.9",
        color: "bg-indigo-500",
    },
    {
        name: "Marcos Lima",
        avatar: "ML",
        role: "Suporte Técnico",
        issuesResolved: 112,
        rating: "4.7",
        color: "bg-emerald-500",
    },
    {
        name: "Patrícia Alves",
        avatar: "PA",
        role: "Especialista Doc",
        issuesResolved: 98,
        rating: "4.8",
        color: "bg-amber-500",
    },
    {
        name: "Felipe Costa",
        avatar: "FC",
        role: "Atendimento N1",
        issuesResolved: 82,
        rating: "4.5",
        color: "bg-rose-500",
    },
];
</script>

<template>
    <Card class="col-span-1 flex h-full flex-col md:col-span-2 lg:col-span-3">
        <CardHeader class="border-b border-border/40 pb-5">
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2 text-lg">
                    Ranking da Equipe
                    <Trophy class="h-4 w-4 text-amber-500" />
                </CardTitle>
            </div>
            <CardDescription class="pt-1.5"
                >Resempenho e resolução no mês atual.</CardDescription
            >
        </CardHeader>

        <CardContent class="flex-1 px-4 pt-5 sm:px-6">
            <div class="space-y-4">
                <div
                    v-for="(member, index) in team"
                    :key="index"
                    class="group -mx-2 flex cursor-default items-center gap-3 rounded-xl p-2 transition-colors hover:bg-muted/40 sm:gap-4"
                >
                    <!-- Ranking Position & Avatar -->
                    <div class="relative shrink-0">
                        <div
                            :class="[
                                member.color,
                                'flex h-10 w-10 items-center justify-center rounded-full font-bold tracking-wider text-white shadow-sm transition-transform group-hover:scale-105 sm:h-11 sm:w-11',
                            ]"
                        >
                            {{ member.avatar }}
                        </div>
                        <!-- Coroa para o primeiro lugar -->
                        <div
                            v-if="index === 0"
                            class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-[10px] text-amber-600 ring-2 ring-background dark:bg-amber-500/20"
                            title="Top 1 do Mês"
                        >
                            👑
                        </div>
                    </div>

                    <!-- Dados do Membro -->
                    <div class="flex-1 truncate">
                        <p
                            class="flex items-center gap-2 truncate text-sm font-semibold text-foreground"
                        >
                            {{ member.name }}
                        </p>
                        <p class="mt-1 truncate text-xs text-muted-foreground">
                            {{ member.role }}
                        </p>
                    </div>

                    <!-- Estatísticas -->
                    <div class="text-right">
                        <div
                            class="flex items-end justify-end gap-1.5 text-sm font-bold text-foreground"
                        >
                            {{ member.issuesResolved }}
                            <span
                                class="mb-0.5 text-[10px] font-medium tracking-wide text-muted-foreground uppercase"
                                >resolvidos</span
                            >
                        </div>
                        <div
                            class="mt-1 flex items-center justify-end gap-1 text-xs font-semibold text-amber-500"
                        >
                            {{ member.rating }}
                            <Star
                                class="h-3 w-3 fill-amber-500 text-amber-500"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>

        <CardFooter class="border-t pt-4">
            <Button
                variant="ghost"
                class="flex w-full items-center gap-2 text-sm"
            >
                Ver Relatório de Desempenho <ArrowRight class="h-3 w-3" />
            </Button>
        </CardFooter>
    </Card>
</template>
