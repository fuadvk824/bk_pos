export interface TransactionItemStore {
    id: number;
    store_code: string;
    name: string;
    stock: number;
}

export interface TransactionItem {
    id: number;
    product_id: number;
    product_name: string;
    stock_at_transaction: string;
    quantity: number;
    fulfillment_status: 'ready' | 'waiting_stock' | 'fulfilled';
    stores: TransactionItemStore[];
}

export interface TransactionDetail {
    id: number;
    invoice_number: string;
    store_id: number;

    store: {
        name: string;
    };

    items: TransactionItem[];
}