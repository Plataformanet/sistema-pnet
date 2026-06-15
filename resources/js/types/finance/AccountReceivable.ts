import { BankAccount } from "./BankAccount";
import { FinanceCategory } from "./FinanceCategory";
import { FinanceSubcategory } from "./FinanceSubcategory";
import { FinancialContact, Cost, AccountPayableInstallment } from "./AccountPayable";

export interface AccountReceivable {
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
    client_id?: string | number; // legacy or fallback
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
