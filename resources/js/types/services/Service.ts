export interface Service {
    id: string | number;
    name: string;
    description?: string;
    price: number;
    category_id: string | number;
    status?: string;
    created_at?: string;
    updated_at?: string;
}
