import { Contact } from "./Contact";

export interface Supplier {
    responsible_person?: string;
    description?: string;
    supply_category: string;
    contact: Contact;
    active?: boolean;
    status?: string;
    created_at?: string;
    updated_at?: string;
}
