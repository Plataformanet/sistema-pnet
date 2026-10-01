<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import TenantLayout from "@/layouts/tenant-layout/TenantLayout.vue";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-vue-next";
import { route } from "ziggy-js";
import BankForm from "../components/BankForm.vue";
import type { Bank } from "@/types";

defineOptions({ layout: TenantLayout });

const props = defineProps<{
    bank: Bank;
}>();

const form = useForm({
    name: props.bank.name || "",
});

function submit() {
    form.put(route('tenant.documents.banks.update', props.bank.id));
}
</script>

<template>
    <Head title="Editar Banco" />

    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Banco
            </h2>
        </div>
        <Button variant="outline" class="cursor-pointer" as-child>
            <Link :href="route('tenant.documents.banks.list')">
                <ChevronLeft class="mr-2 h-4 w-4" /> Voltar
            </Link>
        </Button>
    </div>

    <div class="mx-auto mb-20 max-w-6xl py-4">
        <BankForm :form="form" @submit="submit" submitText="Atualizar Banco" />
    </div>
</template>
