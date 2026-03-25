<script setup lang="ts">
import type { SidebarProps } from "@/components/ui/sidebar";
import { ChevronRight } from "lucide-vue-next";
import SearchForm from "@/layouts/tenant-layout/SearchForm.vue";
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from "@/components/ui/collapsible";
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from "@/components/ui/sidebar";
import { Link } from "@inertiajs/vue3";

const props = defineProps<SidebarProps>();

// This is sample data.
const data = {
    versions: ["1.0.1", "1.1.0-alpha", "2.0.0-beta1"],
    navMain: [
        {
            title: "Dashboard",
            url: "/",
        },
        {
            title: "Vendas",
            url: "/vendas/dashboard",
            items: [
                {
                    title: "Lista orçamentos",
                    url: "/orcamentos/lista",
                },
                {
                    title: "Novo orçamento",
                    url: "/orcamentos/novo",
                },
                {
                    title: "Lista vendas",
                    url: "/vendas/lista",
                },
                {
                    title: "Nova venda",
                    url: "/vendas/novo",
                },
            ],
        },
        {
            title: "Serviços",
            url: "/servicos/dashboard",
            items: [
                {
                    title: "Lista Serviços",
                    url: "/servicos/lista",
                },
                {
                    title: "Novo serviço",
                    url: "/servicos/novo",
                },
            ],
        },
        {
            title: "Documentações",
            url: "/documentacoes/dashboard",
            items: [
                {
                    title: "Propostas",
                    url: "/documentacoes/propostas/lista",
                },
                {
                    title: "Nova proposta",
                    url: "/documentacoes/propostas/novo",
                },
                {
                    title: "Calculadora ITBI",
                    url: "/documentacoes/propostas/novo",
                },
            ],
        },
        {
            title: "Financeiro",
            url: "financeiro/dashboard",
            items: [
                {
                    title: "Categorias/Subcategorias",
                    url: "financeiro/categorias/lista",
                },
                {
                    title: "Contas Bancárias",
                    url: "financeiro/contas-bancarias",
                },
                {
                    title: "Contas a pagar",
                    url: "financeiro/contas-pagar",
                },
                {
                    title: "Contas a receber",
                    url: "financeiro/contas-receber",
                },
                {
                    title: "Fluxo de caixa",
                    url: "financeiro/fluxo-caixa",
                },
                {
                    title: "Fluxo de gastos",
                    url: "financeiro/fluxo-gastos",
                },
                {
                    title: "Faturamentos",
                    url: "financeiro/faturamentos",
                },
            ],
        },
    ],
};

interface NavParentProps {
    title: string;
    url?: string;
    items?: {
        title: string;
        url?: string;
    }[];
}
</script>

<template>
    <Sidebar v-bind="props">
        <SidebarHeader>
            <img
                src="/images/logo-plataformanet-preto.png"
                alt="Logo PlataformaNet"
                class="h-16 max-w-full block mx-auto"
            />
            <SearchForm />
        </SidebarHeader>
        <SidebarContent class="gap-0">
            <template v-for="item in data.navMain" :key="item.title">
                <Collapsible :title="item.title" class="group/collapsible">
                    <SidebarGroup>
                        <SidebarGroupLabel
                            as-child
                            class="group/label text-sm text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                            v-if="item.items"
                        >
                            <CollapsibleTrigger>
                                {{ item.title }}
                                <ChevronRight
                                    class="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90"
                                />
                            </CollapsibleTrigger>
                        </SidebarGroupLabel>
                        <SidebarMenuItem v-else>
                            <SidebarMenuButton as-child class="font-semibold">
                                <Link :href="item.url">{{ item.title }}</Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <CollapsibleContent v-if="item.items">
                            <SidebarGroupContent>
                                <SidebarMenu>
                                    <SidebarMenuItem
                                        v-for="childItem in item.items"
                                        :key="childItem.title"
                                        :title="childItem.title"
                                    >
                                        <SidebarMenuButton as-child>
                                            <Link :href="childItem.url">{{
                                                childItem.title
                                            }}</Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                </SidebarMenu>
                            </SidebarGroupContent>
                        </CollapsibleContent>
                    </SidebarGroup>
                </Collapsible>
            </template>
        </SidebarContent>
        <SidebarRail />
    </Sidebar>
</template>
