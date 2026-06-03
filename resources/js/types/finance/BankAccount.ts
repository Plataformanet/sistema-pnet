export interface BankAccount {
    id: string | number;
    name: string;
    bank: string;
    agency: string;
    account_number: string;
    account_type: string;
    initial_balance?: number | string;
    current_balance?: number | string;
    active: boolean;
    main_account: boolean;
    created_at?: string;
    updated_at?: string;
}
