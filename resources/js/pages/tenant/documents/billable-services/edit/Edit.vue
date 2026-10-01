<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import { centsToMask, parseCurrencyToCents } from "@/lib/masks";
import BillableServiceForm from "../components/BillableServiceForm.vue";
import type { BillableService } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    billableService: BillableService;
}>();

const form = useForm({
    name: props.billableService.name || "",
    description: props.billableService.description || "",
    price: centsToMask(props.billableService.price),
    generates_receipt: props.billableService.generates_receipt ?? false,
});

function submit() {
    const payload = {
        ...form.data(),
        price: parseCurrencyToCents(form.price as string),
    };
    form.transform(() => payload).put(route('tenant.documents.billable-services.update', props.billableService.id));
}
</script>

<template>
    <Head title="Editar Serviço" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Serviço
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.billable-services.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <BillableServiceForm :form="form" @submit="submit" submitText="Atualizar Serviço" />
    </div>
</template>
