import { memo } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatRupiah } from '@/lib/format-rupiah';
import { Minus, Plus, Trash } from 'lucide-react';
import { Separator } from '@/components/ui/separator';

interface CartItem {
    id: number;
    name: string;
    price: number;
    discount: number;
    final_price: number;
    stock: number;

    qty: number;
    customPrice: number;
}

interface Props {
    item: CartItem;
    order: number;

    onUpdateQty: (productId: number, qty: number) => void;
    onUpdatePrice: (productId: number, price: number) => void;
    onRemove: (productId: number) => void;
}

function CartItemCard({
    item,
    order,
    onUpdateQty,
    onUpdatePrice,
    onRemove,
}: Props) {
    const stockError = item.qty > item.stock;
    const defaultPrice = item.discount > 0 ? item.final_price : item.price;

    return (
        <div
            className={`relative mb-3 overflow-hidden rounded-md border p-3 ${
                stockError ? 'border-red-500' : ''
            }`}
        >
            <div className="absolute top-0 right-0">
                <div className="relative h-0 w-0 border-t-[40px] border-l-[40px] border-t-black border-l-transparent">
                    <span className="absolute -top-[35px] -left-[16px] text-[10px] font-bold text-white">
                        {order}
                    </span>
                </div>
            </div>
            <p className="text-xs font-medium">{item.name}</p>

            {item.discount > 0 ? (
                <>
                    <p className="text-[11px] text-gray-400 line-through">
                        {formatRupiah(item.price)}
                    </p>

                    <p className="text-[11px] font-semibold text-green-600">
                        Harga Diskon: {formatRupiah(item.final_price)}
                    </p>
                </>
            ) : (
                <p className="text-[11px] text-gray-500">
                    Harga: {formatRupiah(item.price)}
                </p>
            )}
            <Separator className="mt-1" />

            <div className="my-5 w-full space-y-3">
                <div>
                    <div className="flex justify-between">
                        <Label>Harga Jual</Label>
                        <span className="text-[11px] text-gray-500">
                            (default {formatRupiah(defaultPrice)})
                        </span>
                    </div>

                    <div className="relative">
                        <Input
                            type="text"
                            inputMode="numeric"
                            value={formatRupiah(item.customPrice || 0)}
                            onFocus={(e) => e.target.select()}
                            onChange={(e) => {
                                const raw = e.target.value.replace(/\D/g, '');
                                onUpdatePrice(item.id, raw ? Number(raw) : 0);
                            }}
                            className="pr-16"
                        />

                        <button
                            type="button"
                            onClick={() => onUpdatePrice(item.id, defaultPrice)}
                            className="absolute top-1/2 right-2 -translate-y-1/2 cursor-pointer text-xs text-blue-600 hover:text-blue-800"
                        >
                            Reset
                        </button>
                    </div>
                </div>
                <div>
                    <div className="flex justify-between">
                        <Label>Quantity</Label>
                        <span className="text-[11px] text-gray-500">
                            (Stok: {item.stock})
                        </span>
                    </div>

                    <div className="flex items-center gap-2">
                        <div className="flex flex-1 items-center overflow-hidden rounded-md border">
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() =>
                                    onUpdateQty(item.id, item.qty - 1)
                                }
                                className="cursor-pointer rounded-none border-r px-3"
                            >
                                <Minus className="h-4 w-4" />
                            </Button>

                            <Input
                                type="number"
                                min={1}
                                value={item.qty}
                                onChange={(e) =>
                                    onUpdateQty(item.id, Number(e.target.value))
                                }
                                className="no-spinner border-0 text-center shadow-none focus-visible:ring-0"
                            />

                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() =>
                                    onUpdateQty(item.id, item.qty + 1)
                                }
                                className="cursor-pointer rounded-none border-l px-3"
                            >
                                <Plus className="h-4 w-4" />
                            </Button>
                        </div>

                        <Button
                            type="button"
                            onClick={() => onRemove(item.id)}
                            className="cursor-pointer bg-red-500"
                        >
                            <Trash />
                        </Button>
                    </div>
                </div>
            </div>

            {stockError && (
                <div className="mt-2 rounded bg-red-100 p-2 text-[10px] text-red-700">
                    Jumlah melebihi stok tersedia ({item.stock})
                </div>
            )}

            <div className="mt-2 text-sm font-semibold">
                Subtotal: {formatRupiah(item.qty * item.customPrice)}
            </div>
        </div>
    );
}



export default memo(CartItemCard);
