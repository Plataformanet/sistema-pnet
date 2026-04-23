export interface AccountPayable {
    id: string | number;
    description: string;
    amount: number;
    due_date: string;
    status: string;
    supplier_id?: string | number;
    created_at?: string;
    updated_at?: string;
}
