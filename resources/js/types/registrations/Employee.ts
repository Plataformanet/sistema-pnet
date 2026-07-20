import { Contact } from "./Contact";

export interface Employee {
    rg?: string;
    birth_date?: string;
    position?: string;
    salary?: string;
    hire_date?: string;
    contact?: Contact;
    active?: boolean;
    status?: string;
    role?: string;
    created_at?: string;
    updated_at?: string;
}
