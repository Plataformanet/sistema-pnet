import { Contact } from "./Contact";

export interface Client {
    contact?: Contact;
    status?: string;
    created_at?: string;
    updated_at?: string;
}
