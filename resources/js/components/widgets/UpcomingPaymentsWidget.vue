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
    CalendarClock,
    ArrowRight,
    Wallet,
    Banknote,
    Receipt,
} from "lucide-vue-next";

// Dados simulados de contas a pagar
const payments = [
    {
        id: 1,
        description: "Aluguel Sala Comercial",
        category: "Imóveis",
        value: "R$ 4.500,00",
        dueDate: "Vence Hoje",
        urgency: "critical", // "Vence Hoje"
        icon: Banknote,
    },
    {
        id: 2,
        description: "Assinatura Software Jurídico",
        category: "Sistemas",
        value: "R$ 350,00",
        dueDate: "Amanhã",
        urgency: "warning",
        icon: Receipt,
    },
    {
        id: 3,
        description: "Pagamento Fornecedor (Limpeza)",
        category: "Serviços",
        value: "R$ 1.200,00",
        dueDate: "Em 3 dias",
        urgency: "normal",
        icon: Wallet,
    },
    {
        id: 4,
        description: "Imposto INSS",
        category: "Tributário",
        value: "R$ 8.900,00",
        dueDate: "10/Abril",
        urgency: "normal",
        icon: Receipt,
    },
];

// Helper para estilizar as badges de urgência seguindo o padrão shadcn premium
const getBadgeClasses = (urgency: string) => {
    switch (urgency) {
        case "critical":
            return "bg-rose-500/15 text-rose-600 hover:bg-rose-500/25 dark:bg-rose-500/20 dark:text-rose-400 dark:hover:bg-rose-500/30 border-transparent font-semibold";
        case "warning":
            return "bg-amber-500/15 text-amber-700 hover:bg-amber-500/25 dark:bg-amber-500/20 dark:text-amber-400 dark:hover:bg-amber-500/30 border-transparent font-medium";
        default:
            return "bg-muted/50 text-muted-foreground hover:bg-muted dark:hover:bg-muted border-transparent font-medium";
    }
};
</script>

<template>
    <Card class="col-span-1 flex h-full flex-col md:col-span-2 lg:col-span-4">
        <CardHeader
            class="flex flex-row items-center justify-between border-b border-border/40 pb-5"
        >
            <div>
                <CardTitle class="flex items-center gap-2 text-lg">
                    Próximos Vencimentos
                    <CalendarClock class="h-4 w-4 text-rose-500" />
                </CardTitle>
                <CardDescription class="pt-1.5">
                    Contas a pagar e obrigações da sua semana.
                </CardDescription>
            </div>

            <!-- Resumo no canto do header -->
            <div class="hidden text-right sm:block">
                <span
                    class="block text-[11px] font-medium tracking-wider text-muted-foreground uppercase"
                >
                    A Pagar (7 Dias)
                </span>
                <span class="text-xl font-bold tracking-tight text-foreground">
                    R$ 14.950,00
                </span>
            </div>
        </CardHeader>

        <CardContent class="flex-1 px-4 pt-4 sm:px-6">
            <div class="space-y-1 sm:space-y-2">
                <!-- Lista Flexível simulando linhas de Tabela -->
                <div
                    v-for="item in payments"
                    :key="item.id"
                    class="group relative -mx-3 grid cursor-default grid-cols-[1fr_auto] items-center gap-x-4 gap-y-2 rounded-xl p-3 transition-colors hover:bg-muted/40 sm:-mx-4 sm:grid-cols-12 sm:px-4 sm:py-3"
                >
                    <!-- Ícone + Textos principais -->
                    <div
                        class="col-span-1 flex items-center gap-3 truncate sm:col-span-6 sm:gap-4"
                    >
                        <!-- Box de ícone mais robusta -->
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] border border-border/50 bg-background shadow-sm transition-all group-hover:-translate-y-0.5 group-hover:shadow"
                        >
                            <component
                                :is="item.icon"
                                class="h-4 w-4 text-muted-foreground transition-colors group-hover:text-foreground"
                            />
                        </div>
                        <div class="truncate">
                            <p
                                class="truncate text-sm leading-none font-medium text-foreground"
                            >
                                {{ item.description }}
                            </p>
                            <p
                                class="mt-1.5 truncate text-xs text-muted-foreground"
                            >
                                {{ item.category }}
                            </p>
                        </div>
                    </div>

                    <!-- Coluna Data/Badge (Escondida no topo do mobile, jogamos pra badge flex no fim em mobile) -->
                    <div
                        class="col-span-3 hidden items-center justify-end sm:flex"
                    >
                        <Badge
                            :class="getBadgeClasses(item.urgency)"
                            variant="outline"
                            class="rounded-md"
                        >
                            {{ item.dueDate }}
                        </Badge>
                    </div>

                    <!-- Coluna Valor e Badge no Mobile -->
                    <div
                        class="col-span-1 flex flex-col items-end justify-between gap-2 sm:col-span-3 sm:flex-row sm:items-center sm:justify-end"
                    >
                        <!-- Badge para mobile apenas -->
                        <Badge
                            :class="getBadgeClasses(item.urgency)"
                            variant="outline"
                            class="rounded-md px-1.5 py-0 text-[10px] sm:hidden"
                        >
                            {{ item.dueDate }}
                        </Badge>
                        <span
                            class="text-sm font-semibold tracking-tight text-foreground"
                        >
                            {{ item.value }}
                        </span>
                    </div>
                </div>
            </div>
        </CardContent>

        <CardFooter class="border-t pt-4">
            <Button
                variant="ghost"
                class="flex w-full items-center justify-center gap-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase transition-colors hover:text-foreground"
            >
                Acessar Financeiro Completo
                <ArrowRight class="h-3 w-3" />
            </Button>
        </CardFooter>
    </Card>
</template>
