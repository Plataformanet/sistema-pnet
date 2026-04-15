<script setup lang="ts">
import { MoreHorizontal, Eye, Pencil, Trash } from "lucide-vue-next";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Button } from "@/components/ui/button";
import { Link, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps<{
    employee: { id: string };
}>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" class="h-8 w-8 p-0">
                <span class="sr-only">Abrir menu</span>
                <MoreHorizontal class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuLabel>Ações</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="console.log('Visualizar', employee.id)">
                <Eye class="mr-2 h-4 w-4" /> Visualizar
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link
                    :href="
                        route(
                            'tenant.registrations.employees.edit',
                            employee.id,
                        )
                    "
                    class="flex w-full cursor-pointer items-center"
                >
                    <Pencil class="mr-2 h-4 w-4" /> Editar
                </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                @click="console.log('Excluir', employee.id)"
                class="text-red-600"
            >
                <Trash class="mr-2 h-4 w-4" /> Excluir
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
