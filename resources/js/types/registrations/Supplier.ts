export interface Supplier {
    id: string | number;
    type?: string;
    name?: string;
    cpf?: string;
    corporate_reason?: string;
    fantasy_name?: string;
    cnpj?: string;
    contact_name?: string;
    description?: string;
    categories?: string[];
    email?: string;
    phone?: string;
    cellphone?: string;
    zipcode?: string;
    street?: string;
    number?: string;
    complement?: string;
    neighborhood?: string;
    city?: string;
    state?: string;
    status?: string;
    created_at?: string;
    updated_at?: string;
}
