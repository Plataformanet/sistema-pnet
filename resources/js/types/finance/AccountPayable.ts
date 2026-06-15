import { BankAccount } from "./BankAccount";
import { FinanceCategory } from "./FinanceCategory";
import { FinanceSubcategory } from "./FinanceSubcategory";
import { Contact } from "../registrations/Contact";

export interface Cost {
    id: string | number;
    type: string;
    created_at?: string;
    updated_at?: string;
}

export interface AccountPayableInstallment {
    id: string | number;
    account_payable_id: string | number;
    installment_number: number;
    value: number; // in cents
    due_date: string;
    payment_date?: string | null;
    status: string; // 'open' | 'paid' | 'overdue'
    created_at?: string;
    updated_at?: string;
}

export interface FinancialContact {
    id: string | number;
    contact_id?: string | number;
    type?: string;
    contact?: Contact;
}

export interface AccountPayable {
    id: string | number;
    description: string;
    total: number; // in cents
    total_installments: number;
    payment_condition: string;
    payment_method: string | number;
    observations?: string | null;
    receipt?: string | null;
    status: string;
    due_date?: string; // fallback or legacy
    supplier_id?: string | number; // legacy or fallback
    financial_category_id?: string | number | null;
    financial_subcategory_id?: string | number | null;
    cost_id?: string | number | null;
    bank_account_id?: string | number | null;
    bank_account_out?: string | number | null;
    financial_contact_id?: string | number | null;
    created_at?: string;
    updated_at?: string;

    // Relations
    bank_account?: BankAccount;
    financial_category?: FinanceCategory;
    financial_subcategory?: FinanceSubcategory;
    financial_contact?: FinancialContact;
    cost?: Cost;
    installments?: AccountPayableInstallment[];
}
