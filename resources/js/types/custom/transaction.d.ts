export interface Transaction {
    id: number;

    invoice_number: string;

    customer_id?: number | null;
    customer_name?: string | null;
    customer_address?: string | null;

    store_id: number;
    store_name?: string | null;

    total: number;

    payment_status: string;

    transaction_type?: string | null;

    delivery_type?: string | null;

    driver_name?: string | null;

    notes?: string | null;

    created_at?: string | null;

    // ==================================================
    // FULFILLMENT
    // ==================================================

    waiting_stock_count: number;
    ready_count: number;
    fulfilled_count: number;
    is_backorder: boolean;
}
// export interface Transaction {
//     id: number;
//     invoice_number: string;
//     customer_name: string | null;
//     customer_address: string | null;
//     store_name: string | null;

//     total: number;
//     paid_amount: number;

//     remaining_amount: number;
//     payment_status: 'unpaid' | 'partial' | 'paid';
//     created_at: string;

//     delivery_type: 'pickup' | 'delivery';
//     driver_name: string | null;
//     is_backorder: boolean;
// }
