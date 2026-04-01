<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    CardFooter,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    AlertTriangle,
    Clock,
    MoreHorizontal,
    ArrowRight,
} from "lucide-vue-next";
</script>

<template>
    <Card class="flex h-full flex-col col-span-1 md:col-span-2">
        <CardHeader class="flex flex-row items-center justify-between pb-4">
            <div>
                <CardTitle class="text-lg">Pendências Críticas</CardTitle>
                <CardDescription
                    >Processos travados na fila de
                    financiamento.</CardDescription
                >
            </div>

            <!-- Botão de opções fantasma do shadcn -->
            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8 text-muted-foreground hover:text-foreground"
            >
                <MoreHorizontal class="h-4 w-4" />
            </Button>
        </CardHeader>

        <CardContent class="flex-1 px-4 sm:px-6">
            <div class="space-y-6">
                <!-- Item 1 (Crítico) -->
                <div class="flex items-center space-x-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive/15 text-destructive"
                    >
                        <AlertTriangle class="h-5 w-5" />
                    </div>
                    <div class="flex-1 space-y-1 truncate">
                        <p
                            class="text-sm font-medium leading-none text-foreground"
                        >
                            Imobiliária Alfa - Contrato #1402
                        </p>
                        <p class="text-sm text-destructive truncate">
                            Falta RG dos Compradores
                        </p>
                    </div>
                    <!-- Badge Vermelho Customizado -->
                    <Badge variant="destructive" class="ml-auto hidden sm:flex">
                        2 dias travado
                    </Badge>
                </div>

                <!-- Item 2 (Atenção/Aguardando) -->
                <div class="flex items-center space-x-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-500/15 text-amber-500"
                    >
                        <Clock class="h-5 w-5" />
                    </div>
                    <div class="flex-1 space-y-1 truncate">
                        <p
                            class="text-sm font-medium leading-none text-foreground"
                        >
                            Roberto Almeida (Avaliação)
                        </p>
                        <p class="text-sm text-muted-foreground truncate">
                            Aguardando Laudo de Engenharia
                        </p>
                    </div>
                    <!-- Badge Secundário (Amber) -->
                    <Badge
                        variant="outline"
                        class="text-amber-500 border-amber-500/30 ml-auto hidden sm:flex"
                    >
                        Próximo do Prazo
                    </Badge>
                </div>
            </div>
        </CardContent>

        <CardFooter class="border-t pt-4">
            <Button
                variant="ghost"
                class="w-full text-primary hover:text-primary/80 transition-colors flex items-center justify-center gap-2"
            >
                Ver todas as 12 pendências
                <ArrowRight class="h-4 w-4" />
            </Button>
        </CardFooter>
    </Card>
</template>
