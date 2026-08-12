import { memo } from 'react';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/format-rupiah';
import { ShoppingCart } from 'lucide-react';

interface Product {
    id: number;
    name: string;
    product_code: string;
    price: number;
    discount: number;
    final_price: number;
    stock: number;
}

interface ProductListProps {
    products: Product[];
    addToCart: (product: Product) => void;
}

function ProductList({ products, addToCart }: ProductListProps) {
    return (
        <div className="grid grid-cols-2 gap-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5">
            {products.map((p) => (
                <div
                    key={p.id}
                    className="flex flex-col rounded-md border bg-white p-2 shadow-sm transition hover:shadow-md"
                >
                    <div className="relative mb-3 flex h-40 items-center justify-center rounded-md bg-gray-100">
                        <span className="text-xs text-gray-400">No Image</span>
                        <div
                            className={`absolute bottom-2 left-2 mt-2 rounded-md bg-white px-3 py-1.5 text-xs font-medium ${
                                p.stock <= 0
                                    ? 'text-red-600'
                                    : p.stock < 10
                                      ? 'text-yellow-600'
                                      : 'text-green-600'
                            }`}
                        >
                            Stok: {p.stock}
                        </div>
                    </div>

                    <div className="flex flex-1 flex-col">
                        <h3 className="line-clamp-2 text-xs font-semibold">
                            {p.name}
                        </h3>

                        <p className="text-[10px] text-gray-500">
                            {p.product_code}
                        </p>

                        <div className="my-2">
                            {p.discount > 0 ? (
                                <div className="">
                                    <div className="flex gap-2">
                                        <p className="text-xs text-gray-400 line-through">
                                            {formatRupiah(p.price)}
                                        </p>
                                        <p className="text-xs font-bold text-gray-900">
                                            {formatRupiah(p.price - p.discount)}
                                        </p>
                                    </div>

                                    <p className="text-[10px] text-red-500">
                                        Hemat {formatRupiah(p.discount)}
                                    </p>
                                </div>
                            ) : (
                                <>
                                    <p className="text-xs font-bold text-gray-900">
                                        {formatRupiah(p.price)}
                                    </p>
                                    <p className="text-[10px] text-green-500">
                                        Harga normal
                                    </p>
                                </>
                            )}
                        </div>

                        <Button
                            className="mt-auto w-full cursor-pointer"
                            onClick={() => addToCart(p)}
                            disabled={p.stock <= 0}
                        >
                            <ShoppingCart />
                            {p.stock <= 0 ? 'Habis' : 'Tambah'}
                        </Button>
                    </div>
                </div>
            ))}
        </div>
    );
}

export default memo(ProductList);
