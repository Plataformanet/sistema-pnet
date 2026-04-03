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
import { Link, usePage } from "@inertiajs/vue3";
import type { TenantNav } from "./TenantLayout.vue";

const props = defineProps<SidebarProps & { data: TenantNav }>();

const page = usePage();

function isActive(url?: string) {
    if (!url) return false;

    const currentPath = page.url.split("?")[0];

    const menuUrl = url.startsWith("/") ? url : `/${url}`;

    return currentPath === menuUrl;
}

// This is sample data.
const data = props.data;
</script>

<template>
    <Sidebar v-bind="props">
        <SidebarHeader>
            <img
                src="/images/logo-plataformanet-preto.png"
                alt="Logo PlataformaNet"
                class="mx-auto block h-16 max-w-full"
            />
            <SearchForm />
        </SidebarHeader>
        <SidebarContent class="gap-0">
            <template v-for="item in data.navMain" :key="item.title">
                <Collapsible
                    :title="item.title"
                    class="group/collapsible"
                    :default-open="
                        item.items?.some((child) => isActive(child.url))
                    "
                >
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
                            <SidebarMenuButton
                                as-child
                                class="font-semibold"
                                :is-active="isActive(item.url)"
                            >
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
                                        <SidebarMenuButton
                                            as-child
                                            :is-active="isActive(childItem.url)"
                                        >
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
