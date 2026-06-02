export interface FinanceCategory {
    id: string | number;
    name: string;
    type: 1 | 2; // 1 = Saída / Despesa, 2 = Entrada / Receita
    observations?: string | null;
    active: boolean;
    created_at?: string;
    updated_at?: string;
}
