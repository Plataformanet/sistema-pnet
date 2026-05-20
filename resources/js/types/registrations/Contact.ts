export interface Contact {
    id: string | number;
    type?: string;
    name_corporatereason?: string;
    cpf_cnpj?: string;
    fantasy_name?: string;
    // categories?: string[];
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
}
