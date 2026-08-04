import { ref } from "vue";
import { toast } from "vue-sonner";
import type { Drive } from "@/types";

export interface ItemToMove {
    id: number;
    type: "file" | "folder";
    name: string;
}

export function useDriveDragDrop() {
    const activeDropTargetId = ref<number | "root" | null>(null);
    const draggedItems = ref<ItemToMove[]>([]);
    const isDraggingInternal = ref(false);

    const handleDragStart = (
        event: DragEvent,
        item: Drive,
        selectedIds: number[],
        allDrives: Drive[]
    ) => {
        if (item.permission_attrs?.disable) {
            event.preventDefault();
            return;
        }

        // Se o item arrastado faz parte dos selecionados, move todos os selecionados
        let items: ItemToMove[] = [];
        if (selectedIds.includes(item.id)) {
            items = selectedIds
                .map((id) => allDrives.find((d) => d.id === id))
                .filter((d): d is Drive => !!d && !d.permission_attrs?.disable)
                .map((d) => ({
                    id: d.document_type === "folder" ? d.drive_folder_id! : d.id,
                    type: d.document_type === "folder" ? ("folder" as const) : ("file" as const),
                    name: d.name,
                }));
        } else {
            items = [
                {
                    id: item.document_type === "folder" ? item.drive_folder_id! : item.id,
                    type: item.document_type === "folder" ? "folder" : "file",
                    name: item.name,
                },
            ];
        }

        draggedItems.value = items;
        isDraggingInternal.value = true;

        if (event.dataTransfer) {
            event.dataTransfer.setData(
                "application/json",
                JSON.stringify({ items })
            );
            event.dataTransfer.effectAllowed = "move";
        }
    };

    const handleDragOverFolder = (
        event: DragEvent,
        targetFolderId: number | null,
        targetDrive?: Drive | null
    ) => {
        // Se for upload de arquivo externo do SO, deixa o handler de upload da página tratar
        const isExternalFile = event.dataTransfer?.types.includes("Files") && !isDraggingInternal.value;
        if (isExternalFile) return;

        // Se a pasta destino estiver desabilitada por permissão, bloqueia o drop
        if (targetDrive?.permission_attrs?.disable) {
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = "none";
            }
            return;
        }

        // Valida se estamos tentando mover uma pasta para dentro dela mesma
        if (targetFolderId !== null && draggedItems.value.some((i) => i.type === "folder" && i.id === targetFolderId)) {
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = "none";
            }
            return;
        }

        // Não permite mover arquivos diretamente para a raiz (targetFolderId === null)
        if (targetFolderId === null && draggedItems.value.some((i) => i.type === "file")) {
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = "none";
            }
            return;
        }

        event.preventDefault();

        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = "move";
        }

        activeDropTargetId.value = targetFolderId ?? "root";
    };

    const handleDragLeaveFolder = (
        event: DragEvent,
        targetFolderId: number | null
    ) => {
        const expectedTarget = targetFolderId ?? "root";
        if (activeDropTargetId.value === expectedTarget) {
            activeDropTargetId.value = null;
        }
    };

    const handleDropOnFolder = (
        event: DragEvent,
        destinationFolderId: number | null,
        onExecuteMove: (items: ItemToMove[], destinationFolderId: number) => void
    ) => {
        // Se for upload externo de arquivos do SO, ignora aqui
        if (event.dataTransfer?.types.includes("Files") && !isDraggingInternal.value) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        activeDropTargetId.value = null;
        isDraggingInternal.value = false;

        let items = draggedItems.value;

        if (items.length === 0 && event.dataTransfer) {
            try {
                const raw = event.dataTransfer.getData("application/json");
                if (raw) {
                    const parsed = JSON.parse(raw);
                    items = parsed.items || [];
                }
            } catch (e) {
                console.error("Erro ao ler dados de drag:", e);
            }
        }

        if (items.length === 0) return;

        // Filtra para evitar mover pasta para dentro dela mesma
        const destId = destinationFolderId ?? 0;
        const validItems = items.filter(
            (item) => !(item.type === "folder" && item.id === destId)
        );

        if (validItems.length > 0) {
            if ((destinationFolderId === null || destinationFolderId === 0) && validItems.some((i) => i.type === "file")) {
                toast.error("Não é possível mover arquivos diretamente para a raiz do drive.");
                draggedItems.value = [];
                return;
            }

            onExecuteMove(validItems, destId);
        }

        draggedItems.value = [];
    };

    const handleDragEnd = () => {
        isDraggingInternal.value = false;
        activeDropTargetId.value = null;
        draggedItems.value = [];
    };

    return {
        activeDropTargetId,
        isDraggingInternal,
        draggedItems,
        handleDragStart,
        handleDragOverFolder,
        handleDragLeaveFolder,
        handleDropOnFolder,
        handleDragEnd,
    };
}
