<script setup lang="ts">
import { computed, nextTick, ref, watch } from "vue";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Button } from "@/components/ui/button";
import { Check, ChevronsUpDown, Loader2, Search } from "lucide-vue-next";
import { cn } from "@/lib/utils";

interface ComboboxOption {
    value: string | number;
    label: string;
}

/**
 * Select com campo de busca para listas já carregadas no cliente. A busca
 * ignora maiúsculas e acentos ("sao" encontra "São Paulo"). Para buscar no
 * servidor, use o `ComboboxRemote`.
 */
defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        modelValue?: string | number | null;
        options: ComboboxOption[];
        id?: string;
        placeholder?: string;
        searchPlaceholder?: string;
        noResultsText?: string;
        disabled?: boolean;
        loading?: boolean;
    }>(),
    {
        modelValue: null,
        placeholder: "Selecione...",
        searchPlaceholder: "Pesquisar...",
        noResultsText: "Nenhum registro encontrado.",
        disabled: false,
        loading: false,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: string | number): void;
}>();

const isOpen = ref(false);
const search = ref("");
const highlighted = ref(0);
const searchInput = ref<HTMLInputElement | null>(null);
const list = ref<HTMLElement | null>(null);

const normalize = (text: string) =>
    text.normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().trim();

const selectedOption = computed(() => props.options.find((option) => String(option.value) === String(props.modelValue)) ?? null);

const filtered = computed(() => {
    const term = normalize(search.value);

    return term ? props.options.filter((option) => normalize(option.label).includes(term)) : props.options;
});

watch(filtered, () => (highlighted.value = 0));

watch(isOpen, async (open) => {
    if (!open) {
        return;
    }

    search.value = "";
    highlighted.value = Math.max(filtered.value.findIndex((option) => String(option.value) === String(props.modelValue)), 0);

    await nextTick();
    searchInput.value?.focus();
    scrollToHighlighted();
});

function scrollToHighlighted() {
    list.value?.querySelector<HTMLElement>(`[data-index="${highlighted.value}"]`)?.scrollIntoView({ block: "nearest" });
}

function move(step: number) {
    if (!filtered.value.length) {
        return;
    }

    highlighted.value = (highlighted.value + step + filtered.value.length) % filtered.value.length;
    nextTick(scrollToHighlighted);
}

function choose(option: ComboboxOption | undefined) {
    if (!option) {
        return;
    }

    emit("update:modelValue", option.value);
    isOpen.value = false;
}
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                v-bind="$attrs"
                :id="id"
                type="button"
                variant="outline"
                role="combobox"
                :aria-expanded="isOpen"
                :disabled="disabled"
                class="w-full justify-between border-input bg-background text-left font-normal shadow-xs transition-[color,box-shadow] outline-none hover:bg-muted/50"
                :class="cn(!selectedOption && 'text-muted-foreground')"
            >
                <span class="truncate">{{ selectedOption ? selectedOption.label : placeholder }}</span>
                <Loader2 v-if="loading" class="ml-2 h-4 w-4 shrink-0 animate-spin opacity-50" />
                <ChevronsUpDown v-else class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-(--reka-popover-trigger-width) min-w-[240px] p-0" align="start">
            <div class="flex items-center border-b border-border bg-muted/20 px-3 py-2">
                <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                <input
                    ref="searchInput"
                    v-model="search"
                    :placeholder="searchPlaceholder"
                    class="flex h-8 w-full rounded-md bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    @keydown.down.prevent="move(1)"
                    @keydown.up.prevent="move(-1)"
                    @keydown.enter.prevent="choose(filtered[highlighted])"
                />
            </div>

            <div ref="list" role="listbox" class="max-h-[260px] space-y-0.5 overflow-y-auto p-1">
                <div v-if="!filtered.length" class="py-4 text-center text-sm text-muted-foreground">{{ noResultsText }}</div>

                <button
                    v-for="(option, index) in filtered"
                    :key="option.value"
                    type="button"
                    role="option"
                    :data-index="index"
                    :aria-selected="String(option.value) === String(modelValue)"
                    class="relative flex w-full cursor-pointer items-center rounded-sm py-1.5 pr-2 pl-8 text-left text-sm outline-none select-none"
                    :class="index === highlighted ? 'bg-accent text-accent-foreground' : ''"
                    @mouseenter="highlighted = index"
                    @click="choose(option)"
                >
                    <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                        <Check v-if="String(option.value) === String(modelValue)" class="h-4 w-4" />
                    </span>
                    <span class="truncate">{{ option.label }}</span>
                </button>
            </div>
        </PopoverContent>
    </Popover>
</template>
