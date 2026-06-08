export type FinanceCategoryType = "despesa" | "receita";

export interface FinanceCategory {
    id: string | number;
    name: string;
    type: FinanceCategoryType; // despesa = Saída / Despesa, receita = Entrada / Receita
    observations?: string | null;
    active: boolean;
    created_at?: string;
    updated_at?: string;
}
