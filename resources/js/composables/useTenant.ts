import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

export interface TenantProps {
    id: string;
    name: string;
    domain: string;
    plan?: string;
    hasModules: {
        [key: string]: boolean;
    };
}

export function useTenant() {
    const page = usePage();

    const tenant = computed(() => page.props.tenant as TenantProps | null);
    const isTenantContext = computed(() => !!page.props.tenant);

    return {
        tenant,
        isTenantContext,
    };
}
