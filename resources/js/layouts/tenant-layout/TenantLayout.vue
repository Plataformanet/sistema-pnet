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
import { useTenant } from "@/composables/useTenant";
import { Avatar, AvatarImage } from "@/components/ui/avatar";
import AvatarFallback from "@/components/ui/avatar/AvatarFallback.vue";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { LogOut, User } from "lucide-vue-next";

export interface TenantNav {
    navMain: TenantNavItem[];
}

export interface TenantNavItem {
    title: string;
    module?: string;
    permission?: string;
    url: string;
    items?: TenantNavItem[];
}

// This is sample data.
const data: TenantNav = {
    navMain: [
        {
            title: "Dashboard",
            module: "",
            url: "/dashboard",
        },
        {
            title: "Cadastros",
            module: "registrations",
            url: "/registrations/dashboard",
            items: [
                {
                    title: "Clientes",
                    permission: "registrations.clients.view",
                    url: "/registrations/clients/list",
                },
                {
                    title: "Fornecedores",
                    permission: "registrations.suppliers.view",
                    url: "/registrations/suppliers/list",
                },
                {
                    title: "Funcionários",
                    permission: "registrations.employees.view",
                    url: "/registrations/employees/list",
                },
            ],
        },
        {
            title: "Vendas",
            module: "sales",
            url: "/sales/dashboard",
            items: [
                {
                    title: "Lista orçamentos",
                    permission: "sales.quotations.view",
                    url: "/sales/quotes/list",
                },
                {
                    title: "Novo orçamento",
                    permission: "sales.quotations.create",
                    url: "/sales/quotes/new",
                },
                {
                    title: "Lista vendas",
                    permission: "sales.sales.view",
                    url: "/sales/sales/list",
                },
                {
                    title: "Nova venda",
                    permission: "sales.sales.create",
                    url: "/sales/sales/new",
                },
            ],
        },
        {
            title: "Serviços",
            module: "services",
            url: "/services/services/list",
            items: [
                {
                    title: "Lista Serviços",
                    permission: "services.services.view",
                    url: "/services/services/list",
                },
                {
                    title: "Novo serviço",
                    permission: "services.services.create",
                    url: "/services/services/create",
                },
                {
                    title: "Categorias",
                    permission: "services.categories.view",
                    url: "/services/categories/list",
                },
            ],
        },
        {
            title: "Produtos",
            module: "products",
            url: "/products/products/list",
            items: [
                {
                    title: "Lista de Produtos",
                    permission: "products.products.view",
                    url: "/products/products/list",
                },
                {
                    title: "Novo Produto",
                    permission: "products.products.create",
                    url: "/products/products/create",
                },
                {
                    title: "Categorias",
                    permission: "products.categories.view",
                    url: "/products/categories/list",
                },
            ],
        },
        {
            title: "Documentações",
            module: "documents",
            url: "/documentation/dashboard",
            items: [
                {
                    title: "Propostas",
                    permission: "documents.proposals.view",
                    url: "/documentation/proposals/list",
                },
                {
                    title: "Nova proposta",
                    permission: "documents.proposals.create",
                    url: "/documentation/proposals/new",
                },
                {
                    title: "Calculadora ITBI",
                    permission: "documents.itbi_calculator.view",
                    url: "/documentation/itbi-calculator/new",
                },
            ],
        },
        {
            title: "Financeiro",
            module: "finance",
            url: "/financeiro/dashboard",
            items: [
                {
                    title: "Categorias/Subcategorias",
                    permission: "finance.categories.view",
                    url: "/financeiro/categorias/lista",
                },
                {
                    title: "Contas Bancárias",
                    permission: "finance.accounts.view",
                    url: "/financeiro/contas-bancarias",
                },
                {
                    title: "Contas a pagar",
                    permission: "finance.accounts_payable.view",
                    url: "/financeiro/contas-pagar",
                },
                {
                    title: "Contas a receber",
                    permission: "finance.accounts_receivable.view",
                    url: "/financeiro/contas-receber",
                },
                {
                    title: "Fluxo de caixa",
                    permission: "finance.cash_flow.view",
                    url: "/financeiro/fluxo-caixa",
                },
                {
                    title: "Fluxo de gastos",
                    permission: "finance.expenses_flow.view",
                    url: "/financeiro/fluxo-gastos",
                },
                {
                    title: "Faturamentos",
                    permission: "finance.billing.view",
                    url: "/financeiro/faturamentos",
                },
            ],
        },
        {
            title: "Configurações",
            module: "settings",
            url: "/settings/roles/list",
            items: [
                {
                    title: "Cargos",
                    permission: "settings.roles.view",
                    url: "/settings/roles/list",
                },
                {
                    title: "Usuários",
                    permission: "settings.users.view",
                    url: "/settings/users/list",
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

const { tenant } = useTenant();
</script>

<template>
    <SidebarProvider>
        <TenantSidebar :data="data" />
        <SidebarInset>
            <header
                class="sticky top-0 z-10 flex h-16 shrink-0 items-center gap-2 border-b bg-background px-4"
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
                <div class="ml-auto">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Avatar
                                class="flex cursor-pointer items-center justify-center bg-accent"
                            >
                                <!-- <AvatarImage
                                    src="https://github.com/alanvf1.png"
                                /> -->
                                <!-- <AvatarFallback>AV</AvatarFallback> -->
                                <User class="w-4" />
                            </Avatar>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="mr-4">
                            <DropdownMenuLabel>{{
                                tenant?.name
                            }}</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem><User />Perfil</DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <Link :href="route('logout')">
                                    <LogOut />Sair
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>
            <main class="flex-1 overflow-auto p-4">
                <slot />
            </main>
        </SidebarInset>
    </SidebarProvider>
</template>

<style scoped></style>
