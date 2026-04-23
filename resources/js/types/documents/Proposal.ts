export interface Proposal {
    id: string | number;
    title: string;
    content?: string;
    client_id?: string | number;
    status: string;
    created_at?: string;
    updated_at?: string;
}
