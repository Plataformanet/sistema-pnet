export interface ItbiCalculation {
    id: string | number;
    property_value: number;
    rate: number;
    calculated_value: number;
    client_id?: string | number;
    created_at?: string;
    updated_at?: string;
}
