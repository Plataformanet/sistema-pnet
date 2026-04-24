export interface Role {
    id?: string | number;
    name: string;
    permissions: string[];
    created_at?: string;
    updated_at?: string;
}
