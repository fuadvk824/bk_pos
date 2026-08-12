export type Product = {
    id: number;
    product_code: string;
    name: string;
    category: string;
    stock_all: number;
    image?: string | null;
};