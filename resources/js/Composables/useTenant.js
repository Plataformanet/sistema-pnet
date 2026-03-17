import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function useTenant() {
    const page = usePage()

    const tenant = computed(() => page.props.tenant)
    const isTenantContext = computed(() => !!page.props.tenant)

    return {
        tenant,
        isTenantContext,
    }
}
