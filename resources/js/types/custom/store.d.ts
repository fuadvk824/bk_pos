export type Store = {
    id: number;
    company_id: number;
    store_code: string;
    name: string;
    address: string | null;

    target?: {
        id: number;
        year: number;
        month: number;
        target_amount: number;
        status: 'active' | 'inactive';
    } | null;
};

