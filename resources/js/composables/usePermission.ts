import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

export interface PermissionsProps {
    permissions: string[];
}

export function usePermission() {
    const page = usePage();

    const permissions = computed(() => (page.props.permissions ?? []) as string[]);
    const hasPermissions = computed(() => permissions.value.length > 0);

    return {
        permissions,
        hasPermissions,
    };
}
