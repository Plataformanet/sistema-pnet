export interface Billing {
    id: string | number;
    reference_month: string;
    total_amount: number;
    status: string;
    created_at?: string;
    updated_at?: string;
}
