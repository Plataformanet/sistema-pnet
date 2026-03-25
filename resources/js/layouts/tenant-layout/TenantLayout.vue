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
import { Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";
</script>

<template>
    <SidebarProvider>
        <TenantSidebar />
        <SidebarInset>
            <header
                class="bg-background sticky top-0 flex h-16 shrink-0 items-center gap-2 border-b px-4"
            >
                <SidebarTrigger class="-ml-1" />
                <Separator orientation="vertical" class="mr-2 h-4" />
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem class="hidden md:block">
                            <Link :href="route('tenant.dashboard')">
                                Home
                            </Link>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator class="hidden md:block" />

                        <BreadcrumbItem>
                            <BreadcrumbPage>teste</BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>
            <slot />
        </SidebarInset>
    </SidebarProvider>
</template>

<style scoped></style>
