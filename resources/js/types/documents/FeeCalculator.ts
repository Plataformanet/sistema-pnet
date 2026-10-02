export interface FeeBreakdownColumn {
    key: string;
    label: string;
    numeric: boolean;
}

/** Tabela de resultado montada no servidor (valores em centavos). */
export interface FeeBreakdown {
    columns: FeeBreakdownColumn[];
    acts: Array<Record<string, string | number | null>>;
    subtotals: Record<string, number>;
    extra_fees: Array<{ description: string; amount: number }>;
    calculation_total: number;
    itbi: number | null;
    services: Array<{ name: string; amount: number }>;
    grand_total: number;
    show_grand_total: boolean;
    extra_information: string | null;
}

export interface FeeCalculationSummary {
    id: string;
    type: { value: number; label: string };
    state: string;
    municipality_name: string;
    input: Record<string, unknown>;
    discount: string | null;
    expires_at: string;
    breakdown: FeeBreakdown;
}

/** Campos do formulário de cálculo (valores em centavos). */
export interface FeeCalculatorFormData {
    state: string | null;
    municipality_ibge_code: number | null;
    municipality_name: string | null;
    type: number;
    property_value: number | null;
    financing_value: number | null;
    financing_system: string | null;
    first_property: boolean | null;
    discount: string | null;
}

export interface SupportedStateOption {
    value: string;
    label: string;
    capital: string;
    types: number[];
}

export interface CalculationTypeOption {
    value: number;
    label: string;
    description: string;
    summary: string;
}

export interface FeeDiscountOption {
    value: string;
    label: string;
    legal_text: string;
    states: string[] | null;
}

export interface MunicipalityOption {
    ibge_code: number;
    name: string;
    has_itbi: boolean;
    itbi_module: string | null;
}

export interface BillableServiceOption {
    id: number;
    name: string;
    description: string;
    price: number;
    generates_receipt: boolean;
}

export interface ServiceSelection {
    id: number;
    amount: number | null;
}

export interface QuoteStatusPayload {
    value: "open" | "expired" | "converted";
    label: string;
    variant: string;
}

export interface QuoteRow {
    id: number;
    number: string;
    name: string;
    cpf: string;
    valid_until: string | null;
    status: QuoteStatusPayload;
}

export interface QuoteDetail {
    id: number;
    number: string;
    name: string;
    cpf: string;
    email: string;
    phone: string;
    profession: string;
    marital_status: number;
    marital_status_label: string;
    bank_id: number;
    bank: string | null;
    creator: string | null;
    valid_until: string | null;
    proposal_id: number | null;
    calculation: { type: string | null; state: string | null; municipality_name: string | null };
    services: Array<{ id: number; name: string; amount: number }>;
    status: QuoteStatusPayload;
    breakdown: FeeBreakdown;
}

export interface ItbiModuleOption {
    value: string;
    label: string;
    fields: string[];
}

export interface ItbiMunicipalityRow {
    id: number;
    name: string;
    state: string;
    ibge_code: number;
    full_rate: string | null;
    module: { value: string; label: string };
    configured: boolean;
}

export interface ItbiMunicipality {
    id: number;
    name: string;
    state: string;
    ibge_code: number;
    module: string;
    full_rate: string | null;
    rate?: Record<string, string | number | null> | null;
    brackets?: Array<{ min_value: number; max_value: number; rate: string; discount_amount: number }>;
}
