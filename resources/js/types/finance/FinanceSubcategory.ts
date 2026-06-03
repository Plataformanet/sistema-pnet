import { FinanceCategory } from "./FinanceCategory";

export interface FinanceSubcategory {
    id: string | number;
    financial_category_id: string | number;
    name: string;
    observations?: string | null;
    active: boolean;
    created_at?: string;
    updated_at?: string;
    financial_category?: FinanceCategory;
}
