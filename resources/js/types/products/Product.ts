export interface Product {
    id: string | number;
    name: string;
    description?: string;
    price: number;
    stock: number;
    category_id: string | number;
    status?: string;
    created_at?: string;
    updated_at?: string;
}
