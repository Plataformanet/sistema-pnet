export interface Employee {
    id: string | number;
    name_corporatereason?: string;
    cpf_cnpj?: string;
    rg?: string;
    birth_date?: string;
    position?: string;
    salary?: string;
    hire_date?: string;
    email?: string;
    phone?: string;
    cell_phone?: string;
    address?: {
        zip_code?: string;
        street?: string;
        number?: string;
        complement?: string;
        neighborhood?: string;
        city?: string;
        state?: string;
    };
    status?: string;
    role?: string;
    created_at?: string;
    updated_at?: string;
}
