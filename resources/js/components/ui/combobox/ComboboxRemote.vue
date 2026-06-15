<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { watchDebounced } from "@vueuse/core";
import axios from "axios";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Check, ChevronsUpDown, Loader2, Search } from "lucide-vue-next";
import { cn } from "@/lib/utils";

interface Item {
    id: string | number;
    name_corporatereason: string;
    [key: string]: any;
}

const props = withDefaults(
    defineProps<{
        modelValue?: string | number | null;
        url: string;
        queryParams?: Record<string, any>;
        placeholder?: string;
        searchPlaceholder?: string;
        noResultsText?: string;
        initialItem?: Item | null;
        disabled?: boolean;
    }>(),
    {
        placeholder: "Selecione...",
        searchPlaceholder: "Pesquisar...",
        noResultsText: "Nenhum registro encontrado.",
        initialItem: null,
        disabled: false,
    }
);

const emits = defineEmits<{
    (e: "update:modelValue", value: string | number | null): void;
    (e: "select", item: Item | null): void;
}>();

const isOpen = ref(false);
const search = ref("");
const items = ref<Item[]>([]);
const isLoading = ref(false);
const selectedItem = ref<Item | null>(props.initialItem);

// Watch for external reset of the model value
watch(
    () => props.modelValue,
    (newVal) => {
        if (!newVal) {
            selectedItem.value = null;
        } else if (selectedItem.value?.id !== newVal) {
            // If the modelValue changed and does not match our current selection,
            // we check if it is in our loaded items list
            const found = items.value.find((item) => String(item.id) === String(newVal));
            if (found) {
                selectedItem.value = found;
            } else if (props.initialItem && String(props.initialItem.id) === String(newVal)) {
                selectedItem.value = props.initialItem;
            } else {
                selectedItem.value = null;
            }
        }
    }
);

// Watch for initialItem changes (especially useful in asynchronous edit fetches)
watch(
    () => props.initialItem,
    (newVal) => {
        if (newVal) {
            selectedItem.value = newVal;
        }
    },
    { immediate: true }
);

async function fetchOptions() {
    isLoading.value = true;
    try {
        const response = await axios.get<Item[]>(props.url, {
            params: {
                search: search.value,
                ...(props.queryParams || {}),
            },
        });
        items.value = response.data || [];
    } catch (error) {
        console.error("Erro ao buscar dados remotos:", error);
        items.value = [];
    } finally {
        isLoading.value = false;
    }
}

// Watch search input with 300ms debounce
watchDebounced(
    search,
    () => {
        if (isOpen.value) {
            fetchOptions();
        }
    },
    { debounce: 300 }
);

// Load initial options when popover is opened
watch(isOpen, (newVal) => {
    if (newVal) {
        search.value = "";
        fetchOptions();
    }
});

function handleSelect(item: Item) {
    selectedItem.value = item;
    emits("update:modelValue", item.id);
    emits("select", item);
    isOpen.value = false;
}
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                type="button"
                variant="outline"
                role="combobox"
                :aria-expanded="isOpen"
                :disabled="disabled"
                class="w-full justify-between font-normal text-left shadow-xs outline-none bg-background hover:bg-muted/50 border-input transition-[color,box-shadow]"
                :class="cn(!selectedItem && 'text-muted-foreground')"
            >
                <span class="truncate">
                    {{ selectedItem ? selectedItem.name_corporatereason : placeholder }}
                </span>
                <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-full min-w-[320px] p-0" align="start">
            <div class="flex items-center border-b border-border px-3 py-2 bg-muted/20">
                <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                <input
                    v-model="search"
                    :placeholder="searchPlaceholder"
                    class="flex h-8 w-full rounded-md bg-transparent text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
                />
                <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin text-muted-foreground" />
            </div>

            <div class="max-h-[220px] overflow-y-auto p-1 space-y-0.5">
                <div
                    v-if="items.length === 0 && !isLoading"
                    class="py-4 text-center text-sm text-muted-foreground"
                >
                    {{ noResultsText }}
                </div>

                <button
                    v-for="item in items"
                    :key="item.id"
                    type="button"
                    class="relative flex w-full cursor-pointer select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground data-disabled:pointer-events-none data-disabled:opacity-50 text-left transition-colors"
                    @click="handleSelect(item)"
                >
                    <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                        <Check
                            v-if="selectedItem && String(selectedItem.id) === String(item.id)"
                            class="h-4 w-4"
                        />
                    </span>
                    <span class="truncate">{{ item.name_corporatereason }}</span>
                </button>
            </div>
        </PopoverContent>
    </Popover>
</template>

<style scoped></style>
