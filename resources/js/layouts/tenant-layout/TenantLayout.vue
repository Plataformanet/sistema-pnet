<script setup lang="ts">
import TenantSidebar from "@/layouts/tenant-layout/TenantSidebar.vue";
import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from "@/components/ui/sidebar";
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";
import { Separator } from "@/components/ui/separator";
import { Link, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { computed } from "vue";

export interface TenantNav {
    navMain: TenantNavItem[];
}

export interface TenantNavItem {
    title: string;
    url: string;
    items?: TenantNavItem[];
}

// This is sample data.
const data = {
    navMain: [
        {
            title: "Dashboard",
            url: "/dashboard",
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
            url: "/financeiro/dashboard",
            items: [
                {
                    title: "Categorias/Subcategorias",
                    url: "/financeiro/categorias/lista",
                },
                {
                    title: "Contas Bancárias",
                    url: "/financeiro/contas-bancarias",
                },
                {
                    title: "Contas a pagar",
                    url: "/financeiro/contas-pagar",
                },
                {
                    title: "Contas a receber",
                    url: "/financeiro/contas-receber",
                },
                {
                    title: "Fluxo de caixa",
                    url: "/financeiro/fluxo-caixa",
                },
                {
                    title: "Fluxo de gastos",
                    url: "/financeiro/fluxo-gastos",
                },
                {
                    title: "Faturamentos",
                    url: "/financeiro/faturamentos",
                },
            ],
        },
    ],
};

const page = usePage();

const breadcrumbs = computed(() => {
    const currentPath = page.url.split("?")[0];
    let match: { title: string; url: string }[] = [];
    let longestMatchLen = -1;

    for (const parent of data.navMain) {
        if (
            currentPath === parent.url ||
            currentPath.startsWith(parent.url + "/")
        ) {
            if (parent.url.length > longestMatchLen) {
                match = [{ title: parent.title, url: parent.url }];
                longestMatchLen = parent.url.length;
            }
        }
        if (parent.items) {
            for (const child of parent.items) {
                if (
                    currentPath === child.url ||
                    currentPath.startsWith(child.url + "/")
                ) {
                    if (child.url.length > longestMatchLen) {
                        match = [
                            { title: parent.title, url: parent.url },
                            { title: child.title, url: child.url },
                        ];
                        longestMatchLen = child.url.length;
                    }
                }
            }
        }
    }

    return match;
});
</script>

<template>
    <SidebarProvider>
        <TenantSidebar :data="data" />
        <SidebarInset>
            <header
                class="bg-background sticky top-0 flex h-16 shrink-0 items-center gap-2 border-b px-4"
            >
                <SidebarTrigger class="-ml-1" />
                <Separator orientation="vertical" class="mr-2 h-4" />
                <Breadcrumb>
                    <BreadcrumbList>
                        <template
                            v-for="(bc, index) in breadcrumbs"
                            :key="index"
                        >
                            <BreadcrumbItem>
                                <BreadcrumbPage
                                    v-if="index === breadcrumbs.length - 1"
                                >
                                    {{ bc.title }}
                                </BreadcrumbPage>
                                <Link v-else :href="bc.url">
                                    {{ bc.title }}
                                </Link>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator
                                v-if="index < breadcrumbs.length - 1"
                                class="hidden md:block"
                            />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>
            <main class="flex-1 overflow-auto p-4">
                <slot />
            </main>
        </SidebarInset>
    </SidebarProvider>
</template>

<style scoped></style>
