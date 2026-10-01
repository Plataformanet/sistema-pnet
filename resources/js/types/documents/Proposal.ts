import type {
    Bank,
    ContractType,
    CostType,
    Development,
    Notary,
    Option,
    PropertyType,
    Stage,
} from "./Catalog";

export interface StatusOption extends Option {
    color: string;
}

export interface UserOption {
    id: number;
    name: string;
}

export interface ProposalContact {
    id: number;
    type?: string;
    name_corporatereason: string;
    cpf_cnpj: string;
    email: string;
    phone?: string | null;
    cell_phone?: string | null;
}

export interface HolderBankAccount {
    bank_name: string;
    account_type: number;
    branch: string;
    number: string;
    notes?: string | null;
}

export interface Applicant {
    id: number;
    contact: ProposalContact;
    user_id?: number | null;
    birth_date?: string | null;
    marital_status?: number | null;
    profession?: string | null;
    family_income?: number | null;
    declared_income?: number | null;
    declares_income_tax?: boolean;
    by_power_of_attorney?: boolean;
    bank_account?: HolderBankAccount | null;
}

export interface Seller {
    id: number;
    contact: ProposalContact;
    marital_status?: number | null;
    profession?: string | null;
    declared_income?: number | null;
    bank_account?: HolderBankAccount | null;
}

export interface ProposalProperty {
    property_type_id: number;
    development_id?: number | null;
    address?: string | null;
    number?: string | null;
    complement?: string | null;
    block?: string | null;
    unit?: string | null;
    property_type?: PropertyType;
    development?: Development | null;
}

export interface ProposalDocument {
    id: number;
    owner: string;
    documentable_type?: string | null;
    documentable_id?: number | null;
    proposal_stage_id?: number | null;
    type?: string | null;
    title: string;
    original_name: string;
    mime_type?: string | null;
    size: number;
    created_at: string;
    uploader?: UserOption | null;
}

export interface ProposalStage {
    id: number;
    stage_id: number;
    position: number;
    proposal_document_id?: number | null;
    date?: string | null;
    notes?: string | null;
    is_current: boolean;
    started_at?: string | null;
    completed_at?: string | null;
    status: "completed" | "in_progress" | "locked";
    stage: Stage;
    document?: Pick<ProposalDocument, "id" | "title" | "original_name"> | null;
}

export interface ProposalCostItem {
    id: number;
    type: string;
    description?: string | null;
    extra_fee_description?: string | null;
    service_description?: string | null;
    generates_receipt: boolean;
    amount: number;
    cost_type_id?: number | null;
    notary_id?: number | null;
    date?: string | null;
    notes?: string | null;
    has_bill: boolean;
    has_proof: boolean;
    cost_type?: CostType | null;
    notary?: Notary | null;
}

export interface Receipt {
    id: number;
    proposal_cost_item_id?: number | null;
    type: string;
    name: string;
    document: string;
    registration_number?: string | null;
    notary_name?: string | null;
    total_spent?: number | null;
    amount_deposited?: number | null;
    date: string;
}

export interface Proposal {
    id: number;
    number: string;
    creator_id: number;
    analyst_id: number;
    bank_id: number;
    contract_type_id: number;
    amortization_table?: string | null;
    status: string;
    property_condition: string;
    has_other_property: boolean;
    purchase_value: number;
    down_payment_value: number;
    financing_value?: number | null;
    expenses_value?: number | null;
    subsidy_value?: number | null;
    financed_value?: number | null;
    intended_installment_value?: number | null;
    fgts_value?: number | null;
    uses_fgts: boolean;
    is_first_financing: boolean;
    finance_documentation_fee: boolean;
    documentation_fee_to_finance?: number | null;
    payment_term?: number | null;
    declares_income_tax: boolean;
    declared_income?: number | null;
    expected_delivery_month?: number | null;
    expected_delivery_year?: number | null;
    cancellation_reason?: string | null;
    restriction_reason?: string | null;
    contract_notes?: string | null;
    purchase_value_notes?: string | null;
    down_payment_notes?: string | null;
    fgts_notes?: string | null;
    documentation_fee_notes?: string | null;
    documentation_financing_notes?: string | null;
    income_tax_notes?: string | null;
    general_notes?: string | null;
    particularities?: string | null;
    finished_at?: string | null;
    created_at: string;
    creator?: UserOption & { email?: string };
    analyst?: UserOption;
    bank?: Bank;
    contract_type?: ContractType;
    partners?: UserOption[];
    applicants?: Applicant[];
    sellers?: Seller[];
    property?: ProposalProperty | null;
    stages?: ProposalStage[];
    cost_items?: ProposalCostItem[];
    receipts?: Receipt[];
    documents?: ProposalDocument[];
}

export interface ProposalListRow {
    id: number;
    number: string;
    created_at: string;
    applicants: string[];
    creator: string | null;
    property_condition: string;
    purchase_value: number;
    status: StatusOption;
    current_stage: string | null;
}

export interface ChecklistItem {
    owner: string;
    person_id: number | null;
    name: string;
    required: Array<{ value: string; label: string; sent: boolean }>;
    complete: boolean;
}

export interface ProposalAbilities {
    update: boolean;
    delete: boolean;
    manageTimeline: boolean;
    manageFinancial: boolean;
    viewFinancial: boolean;
    uploadDocument: boolean;
    deleteDocument: boolean;
    uploadProof: boolean;
    print: boolean;
}

export interface ProposalFormOptions {
    banks: Pick<Bank, "id" | "name">[];
    contractTypes: Pick<ContractType, "id" | "name" | "requires_financing">[];
    propertyTypes: PropertyType[];
    developments: Pick<Development, "id" | "name">[];
    staff: UserOption[];
    partners: UserOption[];
    maritalStatuses: Option<number>[];
    amortizationTables: Option[];
    propertyConditions: Option[];
    personTypes: Option[];
    bankAccountTypes: Option<number>[];
}
