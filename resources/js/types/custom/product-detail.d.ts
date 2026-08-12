export type ProductDetail = {
    id: number;
    product_code: string;
     barcode: string | null; 
    name: string;
    description?: string;
    image?: string | null;
    category: string;

    stores: {
        id: number;
        name: string;
        stock: number;
        price: number;
        discount: number;
        price_all: number;
    }[];
};