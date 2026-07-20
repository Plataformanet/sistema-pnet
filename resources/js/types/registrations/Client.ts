import { Contact } from "./Contact";

export interface Client {
    contact?: Contact;
    active?: boolean;
    status?: string;
    created_at?: string;
    updated_at?: string;
}
