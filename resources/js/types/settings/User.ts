export interface User {
    id: string | number;
    name: string;
    email: string;
    role: string;
    status: boolean;
    created_at?: string;
    updated_at?: string;
}
