export interface DriveFolder {
    id: number;
    parent_id: number | null;
    name: string;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface DrivePermission {
    id: number;
    drive_id: number;
    user_id: number;
    permission_type:
        | "somente_proprietario"
        | "somente_leitura"
        | "acesso_total";
    user?: {
        id: number;
        name: string;
    };
}

export interface DrivePermissionAttrs {
    has_access: boolean;
    visible: string;
    disable: boolean;
    title: string | null;
}

export type DriveDocumentType =
    | "folder"
    | "pdf"
    | "docx"
    | "xlsx"
    | "txt"
    | "jpg"
    | "png"
    | "zip"
    | "tar";

export interface Drive {
    id: number;
    user_id: number;
    drive_folder_id: number;
    name: string;
    document_path: string;
    document_size: number;
    document_type: DriveDocumentType;
    modified_by: number;
    modified_at: string;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    drive_folder?: DriveFolder;
    drive_permissions?: DrivePermission[];
    created_by?: {
        id: number;
        name: string;
    };
    modified_by_user?: {
        id: number;
        name: string;
    } | null;
    permission_attrs: DrivePermissionAttrs;
    url?: string;
    url_trash?: string;
    size_formated?: string;
    modification_date?: string;
    modification_date_tittle?: string;
}

export interface DriveLogData {
    name: string | null;
    document_path: string | null;
    document_type: string | null;
    deleted_by: string | null;
    deleted_at: string | null;
}

export interface DriveLog {
    id: number;
    log: DriveLogData;
    created_at: string;
    updated_at: string;
}
