import {
    Folder,
    FileText,
    FileSpreadsheet,
    FileImage,
    FileArchive,
    File,
} from "lucide-vue-next";

/**
 * Retorna o componente de ícone correspondente ao tipo de documento
 */
export function getFileIcon(type: string | null) {
    if (!type) return File;
    switch (type) {
        case "folder":
            return Folder;
        case "pdf":
        case "docx":
        case "txt":
            return FileText;
        case "xlsx":
            return FileSpreadsheet;
        case "jpg":
        case "png":
            return FileImage;
        case "zip":
        case "tar":
            return FileArchive;
        default:
            return File;
    }
}

/**
 * Retorna as classes Tailwind para colorir o ícone com base no tipo
 */
export function getIconColorClass(type: string | null): string {
    if (!type) return "text-slate-400";
    switch (type) {
        case "folder":
            return "text-amber-500 fill-amber-500";
        case "pdf":
            return "text-rose-500 fill-rose-50";
        case "docx":
            return "text-blue-500 fill-blue-50";
        case "xlsx":
            return "text-emerald-600 fill-emerald-50";
        case "txt":
            return "text-slate-500";
        case "jpg":
        case "png":
            return "text-violet-500 fill-violet-50";
        case "zip":
        case "tar":
            return "text-orange-500 fill-orange-50";
        default:
            return "text-slate-400";
    }
}

/**
 * Formata um tamanho em bytes para exibição amigável
 */
export function formatSize(bytes: number): string {
    if (bytes === 0) return "---";
    const units = ["Bytes", "KB", "MB", "GB", "TB"];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return parseFloat((bytes / Math.pow(1024, i)).toFixed(1)) + " " + units[i];
}
