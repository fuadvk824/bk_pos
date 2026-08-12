
export interface Transaction {
    id: number;
    invoice_number: string;
    customer_name: string | null;
    customer_address: string | null;
    store_name: string | null;

    total: number;
    paid_amount: number;

    remaining_amount: number;
    payment_status: 'unpaid' | 'partial' | 'paid';
    created_at: string;

    delivery_type: 'pickup' | 'delivery';
    driver_name: string | null;
}