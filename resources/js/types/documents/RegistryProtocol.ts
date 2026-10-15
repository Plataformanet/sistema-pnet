export interface RegistryProtocolPositionOption {
    value: number;
    label: string;
    color: string;
    closed: boolean;
}

export type RegistryProtocolTab = "open" | "closed" | "all";

/** Linha da listagem e conteúdo do painel na timeline da proposta. */
export interface RegistryProtocolRow {
    id: number;
    proposal: { id: number; number: string; deleted: boolean } | null;
    protocol_number: string;
    buyer_name: string;
    seller_name: string | null;
    entered_at: string;
    exited_at: string | null;
    position: RegistryProtocolPositionOption;
}

export interface RegistryProtocolPanelData extends RegistryProtocolRow {
    previous_count: number;
}

/** Opção do Combobox de propostas (formato do ComboboxRemote). */
export interface RegistryProtocolProposalOption {
    id: number;
    number: string;
    applicants: string[];
    name_corporatereason: string;
}

export interface RegistryProtocolFormData {
    id: number;
    proposal_id: number | null;
    protocol_number: string;
    buyer_name: string;
    seller_name: string | null;
    entered_at: string;
    exited_at: string | null;
    position: number;
    proposal: RegistryProtocolProposalOption | null;
}

export interface RegistryProtocolFilters {
    status?: RegistryProtocolTab | null;
    position?: number | string | null;
    search?: string | null;
    proposal_id?: number | string | null;
    page?: number | null;
}
