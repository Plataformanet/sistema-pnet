import { Contact } from "./Contact";

export interface Supplier {
    responsible_person?: string;
    description?: string;
    supply_category: string;
    contact: Contact;
    status?: string;
    created_at?: string;
    updated_at?: string;
}
