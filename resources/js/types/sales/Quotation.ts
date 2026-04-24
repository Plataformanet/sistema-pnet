export interface Quotation {
    id: string | number;
    date: string;
    expiration_date: string;
    total_amount: number;
    status: string;
    client_id: string | number;
    created_at?: string;
    updated_at?: string;
}
