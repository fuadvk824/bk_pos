export interface TransactionItem {
    id: number;
    product_id: number;
    product_name: string;
    stock_at_transaction: string;
    quantity: number;
    base_price: number;
    price: number;
    discount: number;
    subtotal: number;
    fulfillment_status: 'ready' | 'waiting_stock' | 'fulfilled';
    stores: TransactionItemStore[];
}

export interface TransactionItemStore {
    id: number;
    store_code: string;
    name: string;
    stock: number;
}

export interface Payment {
    id: number;
    amount: number;
    payment_method: string;
    paid_at: string;
    cashier_name: string | null;
}

export interface TransactionDetail {
    id: number;
    invoice_number: string;
    subtotal: number;
    shipping_cost: number;
    total: number;

    store_id: number;

    payment_status: string;
    delivery_type: string;
    driver_name?: string;
    notes?: string;
    created_at: string;
    customer: {
        name: string;
        phone?: string;
        address?: string;
    };
    store: {
        name: string;
        address?: string;
    };
    cashier: {
        name: string;
    };
    items: TransactionItem[];
    payments: Payment[];
}
 