export interface Option<T = string> {
    value: T;
    label: string;
}

/** Campos comuns a todos os cadastros do módulo de Documentações. */
export interface CatalogRecord {
    id: number;
    name: string;
    in_use_count?: number;
    deleted_at?: string | null;
    created_at?: string;
    updated_at?: string;
}

export type Bank = CatalogRecord;

export type Development = CatalogRecord;

export interface Notary extends CatalogRecord {
    zip_code: string;
    street: string;
    number: string;
    complement?: string | null;
    neighborhood: string;
    city: string;
    state: string;
    reference_point?: string | null;
    business_hours?: string | null;
}

export interface ContractType extends CatalogRecord {
    requires_financing: boolean;
}

export interface CostType extends CatalogRecord {
    requires_notary: boolean;
    receipt_type: string | null;
}

export interface PropertyType extends CatalogRecord {
    shows_number: boolean;
    shows_complement: boolean;
    requires_development: boolean;
    shows_unit: boolean;
    shows_block: boolean;
}

export interface Stage extends CatalogRecord {
    order: number;
    has_date: boolean;
    date_required: boolean;
    has_upload: boolean;
    upload_required: boolean;
    title_required: boolean;
    notes_required: boolean;
    completion_deadline_hours: number;
    alert_deadline_hours: number;
    shows_property_data: boolean;
    shows_registry_protocol: boolean;
}

export interface BillableService extends CatalogRecord {
    description: string;
    price: number;
    generates_receipt: boolean;
}
