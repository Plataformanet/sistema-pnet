export interface CrmPipeline {
    id: number;
    name: string;
    is_default: boolean;
}

export interface CrmStage {
    id: number;
    crm_pipeline_id: number;
    name: string;
    order: number;
    probability: number;
    color: string;
}

export interface CrmMetrics {
    total_open_value: number;
    won_this_month: number;
    conversion_rate: number;
    active_deals_count: number;
}

export interface CrmDealItem {
    id: number;
    item_type: "product" | "service" | "custom";
    item_id?: number | null;
    name: string;
    unit_price: number;
    quantity: number;
    discount: number;
    total_price: number;
}

export interface CrmActivity {
    id: number;
    type: "call" | "meeting" | "email" | "task" | "note";
    description: string;
    user_name: string;
    created_at: string;
}

export interface CrmDeal {
    id: number;
    title: string;
    contact_id: number;
    contact_name: string;
    contact_phone?: string;
    user_id: number;
    user_name: string;
    crm_pipeline_id: number;
    crm_stage_id: number;
    total_value: number;
    status: "open" | "won" | "lost";
    priority: "baixa" | "media" | "alta";
    expected_close_date?: string;
    created_at: string;
    items?: CrmDealItem[];
    activities?: CrmActivity[];
}
