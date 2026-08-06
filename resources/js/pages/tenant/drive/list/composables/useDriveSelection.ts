import { ref } from "vue";
import type { Drive } from "@/types";

export function useDriveSelection() {
    const selectedDrives = ref<number[]>([]);
    const lastSelectedIndex = ref<number | null>(null);

    const clearSelection = () => {
        selectedDrives.value = [];
        lastSelectedIndex.value = null;
    };

    const isSelected = (id: number) => {
        return selectedDrives.value.includes(id);
    };

    const toggleDriveSelection = (id: number, checked?: boolean) => {
        const isCurrentlySelected = selectedDrives.value.includes(id);
        const targetState = checked !== undefined ? checked : !isCurrentlySelected;

        if (targetState) {
            if (!isCurrentlySelected) {
                selectedDrives.value = [...selectedDrives.value, id];
            }
        } else {
            selectedDrives.value = selectedDrives.value.filter((itemId) => itemId !== id);
        }
    };

    const handleRowClick = (
        event: MouseEvent,
        item: Drive,
        index: number,
        allDrives: Drive[]
    ) => {
        // Se o item estiver desabilitado por falta de permissão, ignora
        if (item.permission_attrs?.disable) return;

        // Evita desmarcar/alternar a seleção no segundo clique de um duplo clique
        if (event.detail > 1) return;

        const isToggleKey = event.ctrlKey || event.metaKey;
        const isShiftKey = event.shiftKey;

        if (isToggleKey) {
            // Seleção individual intercalada (Ctrl no Windows/Linux, Cmd no Mac)
            if (isSelected(item.id)) {
                selectedDrives.value = selectedDrives.value.filter(
                    (id) => id !== item.id
                );
            } else {
                selectedDrives.value = [...selectedDrives.value, item.id];
            }
            lastSelectedIndex.value = index;
        } else if (isShiftKey && lastSelectedIndex.value !== null) {
            // Seleção por intervalo (Shift + Click)
            const start = Math.min(lastSelectedIndex.value, index);
            const end = Math.max(lastSelectedIndex.value, index);

            const rangeIds = allDrives
                .slice(start, end + 1)
                .filter((d) => !d.permission_attrs?.disable)
                .map((d) => d.id);

            // Une com a seleção anterior sem duplicados
            selectedDrives.value = Array.from(
                new Set([...selectedDrives.value, ...rangeIds])
            );
        } else {
            // Clique simples na linha: seleciona apenas o item clicado
            selectedDrives.value = [item.id];
            lastSelectedIndex.value = index;
        }
    };

    const toggleSelectAll = (selectableDrives: Drive[]) => {
        const allSelected =
            selectableDrives.length > 0 &&
            selectedDrives.value.length === selectableDrives.length;

        if (allSelected) {
            clearSelection();
        } else {
            selectedDrives.value = selectableDrives.map((d) => d.id);
        }
    };

    return {
        selectedDrives,
        lastSelectedIndex,
        clearSelection,
        isSelected,
        toggleDriveSelection,
        handleRowClick,
        toggleSelectAll,
    };
}
