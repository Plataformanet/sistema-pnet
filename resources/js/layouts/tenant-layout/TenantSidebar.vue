<script setup lang="ts">
import type { SidebarProps } from "@/components/ui/sidebar";
import { ChevronRight } from "lucide-vue-next";
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
import type {
    TenantSidebarNavigation,
    SidebarNavItem,
} from "./TenantLayout.vue";
import { useTenant } from "@/composables/useTenant";
import { usePermission } from "@/composables/usePermission";
import { computed } from "vue";

const props =
    defineProps<SidebarProps & { navigation: TenantSidebarNavigation }>();

const page = usePage();

const { tenant } = useTenant();
const { permissions } = usePermission();

function isActive(url?: string) {
    if (!url) return false;

    const currentPath = page.url.split("?")[0];
    const menuUrl = url.startsWith("/") ? url : `/${url}`;

    return currentPath === menuUrl;
}

const visibleNavigationItems = computed(() => {
    return props.navigation.navMain
        .map((navGroup) => {
            // Check if module is enabled for tenant
            if (
                navGroup.module &&
                tenant.value?.hasModules &&
                !tenant.value.hasModules[navGroup.module]
            ) {
                return null;
            }

            // Check group direct permission (if specified)
            if (
                navGroup.permission &&
                !permissions.value.includes(navGroup.permission)
            ) {
                return null;
            }

            // Check group sub-items (if specified)
            if (navGroup.items && navGroup.items.length > 0) {
                const visibleSubItems = navGroup.items.filter((subItem) => {
                    if (!subItem.permission) return true;
                    return permissions.value.includes(subItem.permission);
                });

                // Hide module if user has no permissions for any of its sub-items
                if (visibleSubItems.length === 0) {
                    return null;
                }

                return {
                    ...navGroup,
                    items: visibleSubItems,
                };
            }

            return navGroup;
        })
        .filter(
            (navGroup): navGroup is SidebarNavItem => navGroup !== null,
        );
});
</script>

<template>
    <Sidebar v-bind="props">
        <SidebarHeader>
            <img
                src="/images/logo-plataformanet-preto.png"
                alt="Logo PlataformaNet"
                class="mx-auto block h-16 max-w-full"
            />
        </SidebarHeader>
        <SidebarContent class="gap-0">
            <template
                v-for="navGroup in visibleNavigationItems"
                :key="navGroup.title"
            >
                <Collapsible
                    :title="navGroup.title"
                    class="group/collapsible"
                    :default-open="
                        navGroup.items?.some((subItem) => isActive(subItem.url))
                    "
                >
                    <SidebarGroup>
                        <SidebarGroupLabel
                            as-child
                            class="group/label text-sm text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                            v-if="navGroup.items"
                        >
                            <CollapsibleTrigger>
                                {{ navGroup.title }}
                                <ChevronRight
                                    class="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90"
                                />
                            </CollapsibleTrigger>
                        </SidebarGroupLabel>
                        <SidebarMenuItem v-else>
                            <SidebarMenuButton
                                as-child
                                class="font-semibold"
                                :is-active="isActive(navGroup.url)"
                            >
                                <Link :href="navGroup.url">{{
                                    navGroup.title
                                }}</Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <CollapsibleContent v-if="navGroup.items">
                            <SidebarGroupContent>
                                <SidebarMenu>
                                    <SidebarMenuItem
                                        v-for="subItem in navGroup.items"
                                        :key="subItem.title"
                                        :title="subItem.title"
                                    >
                                        <SidebarMenuButton
                                            as-child
                                            :is-active="isActive(subItem.url)"
                                        >
                                            <Link :href="subItem.url">{{
                                                subItem.title
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
