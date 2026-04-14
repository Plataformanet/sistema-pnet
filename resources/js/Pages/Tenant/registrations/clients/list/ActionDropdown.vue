<script setup lang="ts">
import { MoreHorizontal, Eye, Pencil, Trash } from 'lucide-vue-next'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'

const props = defineProps<{
  client: { id: string }
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
      <DropdownMenuItem @click="console.log('Visualizar', client.id)">
        <Eye class="mr-2 h-4 w-4" /> Visualizar
      </DropdownMenuItem>
      <DropdownMenuItem @click="console.log('Editar', client.id)">
        <Pencil class="mr-2 h-4 w-4" /> Editar
      </DropdownMenuItem>
      <DropdownMenuSeparator />
      <DropdownMenuItem @click="console.log('Excluir', client.id)" class="text-red-600">
        <Trash class="mr-2 h-4 w-4" /> Excluir
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
