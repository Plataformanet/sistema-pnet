export interface BankAccount {
    id: string | number;
    bank_name: string;
    agency: string;
    account_number: string;
    balance: number;
    created_at?: string;
    updated_at?: string;
}
