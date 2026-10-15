export interface FinishedProposalRow {
    id: number;
    number: string;
    creator: string | null;
    applicants: string[];
    development: string | null;
    document: { id: number; title: string } | null;
    report_date: string | null;
}

export interface FinishedProposalsFilters {
    start_date?: string | null;
    end_date?: string | null;
    development_id?: number | string | null;
}

export interface ReportDevelopmentOption {
    id: number;
    name: string;
    deleted_at: string | null;
}

export interface ZipLimits {
    sync: number;
    max: number;
}
