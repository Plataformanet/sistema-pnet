export interface FinanceCategory {
    id: string | number;
    name: string;
    type: 'income' | 'expense';
    created_at?: string;
    updated_at?: string;
}
