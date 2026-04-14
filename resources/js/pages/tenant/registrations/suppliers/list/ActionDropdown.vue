<script setup lang="ts">
import { MoreHorizontal, Eye, Pencil, Trash } from 'lucide-vue-next'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps<{
  supplier: { id: string }
}>()
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
      <DropdownMenuItem @click="console.log('Visualizar', supplier.id)">
        <Eye class="mr-2 h-4 w-4" /> Visualizar
      </DropdownMenuItem>
      <DropdownMenuItem as-child>
        <Link :href="route('tenant.registrations.suppliers.edit', supplier.id)" class="w-full flex cursor-pointer items-center">
            <Pencil class="mr-2 h-4 w-4" /> Editar
        </Link>
      </DropdownMenuItem>
      <DropdownMenuSeparator />
      <DropdownMenuItem @click="console.log('Excluir', supplier.id)" class="text-red-600">
        <Trash class="mr-2 h-4 w-4" /> Excluir
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
