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
import { computed, watch, nextTick } from "vue";
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
import { LogOut, User, Sun, Moon } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { useDark, useToggle } from "@vueuse/core";
import { Toaster } from "@/components/ui/sonner";
import { toast } from "vue-sonner";

export interface TenantSidebarNavigation {
    navMain: SidebarNavItem[];
}

export interface SidebarNavItem {
    title: string;
    module?: string;
    permission?: string;
    url: string;
    items?: SidebarNavItem[];
}

const sidebarNavigation: TenantSidebarNavigation = {
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
            url: "#",
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
            url: "#",
            items: [
                {
                    title: "Categorias",
                    permission: "finance.categories.view",
                    url: "/finance/categories/list",
                },
                {
                    title: "Subcategorias",
                    permission: "finance.subcategories.view",
                    url: "/finance/subcategories/list",
                },
                {
                    title: "Contas Bancárias",
                    permission: "finance.accounts.view",
                    url: "/finance/bank-accounts/list",
                },
                {
                    title: "Contas a pagar",
                    permission: "finance.accounts_payable.view",
                    url: "/finance/accounts-payable/list",
                },
                {
                    title: "Contas a receber",
                    permission: "finance.accounts_receivable.view",
                    url: "/finance/accounts-receivable/list",
                },
                {
                    title: "Fluxo de caixa",
                    permission: "finance.cash_flow.view",
                    url: "/finance/cash-flow",
                },
                {
                    title: "Fluxo de gastos",
                    permission: "finance.spending_flow.view",
                    url: "/finance/spending-flow",
                },
                {
                    title: "Faturamentos",
                    permission: "finance.billing.view",
                    url: "/finance/billing",
                },
            ],
        },
        {
            title: "Meu Drive",
            url: "#",
            items: [
                {
                    title: "Lista",
                    permission: "drive.drives.view",
                    url: "/drive",
                },
                {
                    title: "Lixeira",
                    permission: "drive.trash.view",
                    url: "/trash",
                },
                {
                    title: "Logs",
                    permission: "drive.logs.view",
                    url: "/drive/logs",
                },
            ],
        },
        {
            title: "Configurações",
            module: "settings",
            url: "/settings/company",
            items: [
                {
                    title: "Empresa",
                    permission: "settings.company.view",
                    url: "/settings/company",
                },
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

    for (const parent of sidebarNavigation.navMain) {
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

const isDark = useDark();
const toggleDark = useToggle(isDark);

const pageProps = computed(() => usePage().props as any);
const user = computed(() => pageProps.value?.auth?.user);

function getInitials(name?: string) {
    if (!name) return "U";
    return name
        .split(" ")
        .map((part) => part[0])
        .slice(0, 2)
        .join("")
        .toUpperCase();
}

const flash = computed(() => page.props.flash as any);

watch(
    flash,
    async (newFlash) => {
        if (!newFlash) return;

        await nextTick();

        if (newFlash.success) {
            toast.success(newFlash.success);
        }
        if (newFlash.error) {
            toast.error(newFlash.error);
        }
        if (newFlash.warning) {
            toast.warning(newFlash.warning);
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <SidebarProvider>
        <TenantSidebar :navigation="sidebarNavigation" />
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
                <div class="ml-auto flex items-center gap-3">
                    <div v-if="tenant?.name || tenant?.logoUrl" class="hidden md:flex items-center gap-2 px-3 py-1 rounded-full bg-muted/50 border border-border text-xs font-medium text-foreground">
                        <img v-if="tenant?.logoUrl" :src="tenant.logoUrl" :alt="tenant?.name || 'Logo'" class="h-6 w-auto object-contain max-w-[100px]" />
                        <span v-if="tenant?.name">{{ tenant.name }}</span>
                    </div>

                    <!-- Botão Alternar Tema Escuro / Claro -->
                    <Button
                        variant="ghost"
                        size="icon"
                        class="cursor-pointer text-muted-foreground hover:text-foreground rounded-full"
                        @click="toggleDark()"
                        :title="isDark ? 'Mudar para tema claro' : 'Mudar para tema escuro'"
                    >
                        <Sun v-if="isDark" class="h-5 w-5 text-amber-400" />
                        <Moon v-else class="h-5 w-5" />
                    </Button>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Avatar
                                class="flex cursor-pointer items-center justify-center bg-accent border border-border"
                            >
                                <AvatarImage
                                    v-if="user?.photo_url"
                                    :src="user.photo_url"
                                    :alt="user?.name"
                                />
                                <AvatarFallback v-else class="text-xs font-semibold">
                                    {{ getInitials(user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="mr-4">
                            <DropdownMenuLabel>{{
                                user?.name
                            }}</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <Link :href="route('tenant.profile.edit')" class="flex items-center gap-2 cursor-pointer">
                                    <User class="h-4 w-4" />Perfil
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <Link :href="route('tenant.logout')" class="flex items-center gap-2 cursor-pointer">
                                    <LogOut class="h-4 w-4" />Sair
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
    <Toaster richColors closeButton position="bottom-right" />
</template>

<style scoped></style>
