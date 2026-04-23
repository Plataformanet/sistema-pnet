export interface AccountReceivable {
    id: string | number;
    description: string;
    amount: number;
    due_date: string;
    status: string;
    client_id?: string | number;
    created_at?: string;
    updated_at?: string;
}
