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
import { Headphones, Clock, ArrowRight, MessageSquare } from "lucide-vue-next";

const tickets = [
    {
        id: "#4002",
        subject: "Dúvida sobre preenchimento de contrato",
        client: "João Silva",
        priority: "alta",
        time: "15 min atrás",
        status: "aguardando",
    },
    {
        id: "#4001",
        subject: "Alteração de dados cadastrais no sistema",
        client: "Empresa XPTO Ltda",
        priority: "media",
        time: "2 horas atrás",
        status: "em_andamento",
    },
    {
        id: "#4000",
        subject: "Solicitação de segunda via de laudo / certidão",
        client: "Maria Gonçalves",
        priority: "baixa",
        time: "Ontem",
        status: "em_andamento",
    },
];

const getPriorityColor = (priority: string) => {
    if (priority === "alta")
        return "text-rose-600 bg-rose-500/10 dark:text-rose-400 border-rose-500/20";
    if (priority === "media")
        return "text-amber-600 bg-amber-500/10 dark:text-amber-400 border-amber-500/20";
    return "text-emerald-600 bg-emerald-500/10 dark:text-emerald-400 border-emerald-500/20";
};
</script>

<template>
    <Card class="col-span-1 flex h-full flex-col md:col-span-2 lg:col-span-4">
        <CardHeader
            class="flex flex-row items-start justify-between gap-4 border-b border-border/40 pb-5 sm:items-center"
        >
            <div>
                <CardTitle class="flex items-center gap-2 text-lg">
                    Chamados de Serviço
                    <Headphones class="h-4 w-4 text-blue-500" />
                </CardTitle>
                <CardDescription class="pt-1.5">
                    Tickets recentes abertos pelos clientes.
                </CardDescription>
            </div>

            <Badge
                class="shrink-0 border-transparent bg-blue-500/10 font-bold text-blue-700 hover:bg-blue-500/20 dark:text-blue-400"
            >
                12 Aguardando
            </Badge>
        </CardHeader>
        <CardContent class="flex-1 px-4 pt-4 sm:px-6">
            <div class="space-y-2">
                <div
                    v-for="ticket in tickets"
                    :key="ticket.id"
                    class="group -mx-3 flex cursor-pointer flex-col justify-between gap-x-4 gap-y-3 rounded-xl p-3 transition-colors hover:bg-muted/40 sm:-mx-4 sm:flex-row sm:items-center sm:px-4"
                >
                    <div class="flex items-start gap-4 truncate">
                        <!-- Icon -->
                        <div
                            class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border/50 bg-background text-muted-foreground shadow-sm transition-colors group-hover:border-blue-500/30 group-hover:text-blue-500"
                        >
                            <MessageSquare class="h-4 w-4" />
                        </div>
                        <div class="truncate">
                            <h4
                                class="flex items-center gap-2 text-sm font-semibold text-foreground"
                            >
                                <span
                                    class="w-11 shrink-0 font-mono text-muted-foreground"
                                    >{{ ticket.id }}</span
                                >
                                <span
                                    class="block truncate transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400"
                                    >{{ ticket.subject }}</span
                                >
                            </h4>
                            <p
                                class="mt-1.5 flex items-center gap-1.5 truncate text-xs text-muted-foreground"
                            >
                                <span
                                    class="truncate font-medium text-foreground/80"
                                    >{{ ticket.client }}</span
                                >
                                <span
                                    class="inline-block h-1 w-1 shrink-0 rounded-full bg-border"
                                ></span>
                                <span
                                    class="flex shrink-0 items-center gap-1 rounded-md bg-muted/50 px-1.5 py-0.5"
                                    ><Clock class="h-3 w-3" />
                                    {{ ticket.time }}</span
                                >
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex shrink-0 items-center justify-between pl-14 sm:justify-end sm:pl-0"
                    >
                        <Badge
                            :class="getPriorityColor(ticket.priority)"
                            variant="outline"
                            class="rounded-md text-[10px] font-bold tracking-wider uppercase"
                        >
                            Prioridade {{ ticket.priority }}
                        </Badge>
                    </div>
                </div>
            </div>
        </CardContent>
        <CardFooter class="border-t pt-4">
            <Button
                variant="ghost"
                class="flex w-full items-center gap-2 text-sm"
            >
                Acessar Central de Serviços <ArrowRight class="h-3 w-3" />
            </Button>
        </CardFooter>
    </Card>
</template>
