import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import AppLayout from '@/layouts/app-layout';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useRoute } from '@/lib/route-ziggy';
import { formatRupiah } from '@/lib/format-rupiah';
import ProductList from './productList';
import CartItemCard from './cartItemCard';
import CustomerForm from './customerForm';
import DeliveryPaymentForm from './deliveryPaymentForm';

import { Separator } from '@/components/ui/separator';
import { ChevronDown, Loader2, QrCode, Search } from 'lucide-react';
import { toast } from 'sonner';

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Store {
    id: number;
    name: string;
}

interface Product {
    id: number;
    name: string;
    product_code: string;

    price: number;
    discount: number;
    final_price: number;

    stock: number;
}

interface CartItem extends Product {
    qty: number;
    customPrice: number;
}

interface CustomerOption {
    id: number;
    name: string;
    phone: string;
    address: string;
    current_point: number;
}

interface Props {
    stores: Store[];
    products: Product[];

    customers: CustomerOption[];

    filters: {
        store_id?: number;
        customer_search?: string;
    };
}
export default function Index({ stores, products, filters, customers }: Props) {
    const route = useRoute();
    const [processing, setProcessing] = useState(false);
    const [storeId, setStoreId] = useState<number | ''>(filters.store_id ?? '');

    const [showCustomerResult, setShowCustomerResult] = useState(false);
    const [search, setSearch] = useState('');
    const [cart, setCart] = useState<CartItem[]>([]);

    const [customer, setCustomer] = useState({
        id: null as number | null,
        name: '',
        phone: '',
        address: '',
        current_point: 0,
    });

    const [pointsUsed, setPointsUsed] = useState(0);

    const [customerOpen, setCustomerOpen] = useState(true);
    const customerTitle = customer.name
        ? `${customer.name}${customer.phone ? ` • ${customer.phone}` : ''}`
        : 'Data Customer';

    const [deliveryPayment, setDeliveryPayment] = useState({
        deliveryType: 'pickup',
        shippingCost: 0,
        notes: '',
        paymentStatus: 'unpaid',
        paymentMethod: 'cash',
        paymentAmount: 0,
    });
    const [paymentOpen, setPaymentOpen] = useState(false);
    const paymentTitle =
        deliveryPayment.paymentStatus === 'paid'
            ? 'Pembayaran Lunas'
            : deliveryPayment.paymentStatus === 'partial'
              ? `DP ${formatRupiah(deliveryPayment.paymentAmount)}`
              : 'Pengiriman & Pembayaran';

    const handleChangeStore = (id: number) => {
        setStoreId(id);

        router.get(
            route('cashier.index'),
            { store_id: id },
            {
                preserveState: true,
                replace: true,
            },
        );
    };
    const handleCustomerSearch = (value: string) => {
        setShowCustomerResult(value.trim().length > 0);

        router.get(
            route('cashier.index'),
            {
                store_id: storeId,
                customer_search: value,
            },
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
                only: ['customers'],
            },
        );
    };

    const addToCart = (product: Product) => {
        setCart((prev) => {
            const existing = prev.find((c) => c.id === product.id);

            if (existing) {
                if (existing.qty >= product.stock) {
                    return prev;
                }

                return prev.map((c) =>
                    c.id === product.id
                        ? {
                              ...c,
                              qty: c.qty + 1,
                          }
                        : c,
                );
            }

            return [
                ...prev,
                {
                    ...product,
                    qty: 1,
                    customPrice: product.final_price,
                },
            ];
        });
    };

    const updateQty = (productId: number, qty: number) => {
        if (qty < 1) qty = 1;

        setCart((prev) =>
            prev.map((item) =>
                item.id === productId
                    ? {
                          ...item,
                          qty,
                      }
                    : item,
            ),
        );
    };

    const updatePrice = (productId: number, price: number) => {
        if (price < 0) price = 0;

        setCart((prev) =>
            prev.map((item) =>
                item.id === productId
                    ? {
                          ...item,
                          customPrice: price,
                      }
                    : item,
            ),
        );
    };

    const removeFromCart = (productId: number) => {
        setCart((prev) => prev.filter((item) => item.id !== productId));
    };
    const subtotal = useMemo(() => {
        return cart.reduce((sum, item) => sum + item.qty * item.customPrice, 0);
    }, [cart]);
    
    const totalBeforePoint =
        subtotal +
        (deliveryPayment.deliveryType === 'delivery'
            ? Number(deliveryPayment.shippingCost || 0)
            : 0);

    const total = Math.max(0, totalBeforePoint - pointsUsed);

    const hasStockError = cart.some((item) => item.qty > item.stock);

    const filteredProducts = useMemo(() => {
        const keyword = search.toLowerCase();
        return products.filter((product) => {
            return (
                product.name.toLowerCase().includes(keyword) ||
                product.product_code.toLowerCase().includes(keyword)
            );
        });
    }, [products, search]);

    const submit = () => {
        if (processing) return;

        if (hasStockError) {
            toast.error('Masih ada produk yang melebihi stok');
            return;
        }

        if (!storeId) {
            toast.error('Pilih store dulu');
            return;
        }

        if (!cart.length) {
            toast.error('Cart kosong');
            return;
        }

        if (!customer.name.trim() || !customer.phone.trim()) {
            toast.error('Nama No Hp customer wajib diisi');
            return;
        }

        const payload = {
            store_id: storeId,

            name: customer.name,
            phone: customer.phone,
            address: customer.address,

            delivery_type: deliveryPayment.deliveryType,
            points_used: pointsUsed,
            shipping_cost: deliveryPayment.shippingCost,

            notes: deliveryPayment.notes,

            payment_status: deliveryPayment.paymentStatus,

            payment_amount:
                deliveryPayment.paymentStatus === 'paid'
                    ? total
                    : deliveryPayment.paymentStatus === 'partial'
                      ? deliveryPayment.paymentAmount
                      : null,

            payment_method:
                deliveryPayment.paymentStatus === 'unpaid'
                    ? null
                    : deliveryPayment.paymentMethod,

            items: cart.map((c) => ({
                id: c.id,
                qty: c.qty,
                price: c.customPrice,
            })),

            subtotal,
            total,
        };

        setProcessing(true);

        router.post(route('cashier.store'), payload, {
            onError: (errors) => {
                console.log(errors);

                toast.error('Gagal menyimpan transaksi');

                setProcessing(false);
            },

            onSuccess: () => {
                setCart([]);

                setCustomer({
                    id: null,
                    name: '',
                    phone: '',
                    address: '',
                    current_point: 0,
                });

                setShowCustomerResult(false);

                setDeliveryPayment({
                    deliveryType: 'pickup',
                    shippingCost: 0,
                    notes: '',
                    paymentStatus: 'unpaid',
                    paymentMethod: 'cash',
                    paymentAmount: 0,
                });

                toast.success('Transaksi berhasil disimpan');
            },

            onFinish: () => {
                setProcessing(false);
            },
        });
    };
    return (
        <AppLayout
            breadcrumbs={[{ title: 'Kasir', href: route('cashier.index') }]}
        >
            <Head title="Kasir" />
            <div className="grid grid-cols-1 gap-5 p-6 md:grid-cols-2 lg:grid-cols-12">
                <div className="flex h-[calc(100vh-120px)] flex-col md:col-span-2 lg:col-span-9">
                    <div className="mb-5 flex items-center justify-between">
                        <div>
                            <h3 className="text-lg font-bold">Select Produk</h3>
                            <p className="text-xs text-gray-500">
                                Select a product & process to checkout
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <div className="relative flex w-60">
                                <Search
                                    size={18}
                                    className="absolute right-2 translate-y-1/2 text-gray-500"
                                />
                                <Input
                                    placeholder="Cari nama atau kode produk..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>
                            <div className="w-52">
                                <Select
                                    value={storeId ? String(storeId) : ''}
                                    onValueChange={(value) =>
                                        handleChangeStore(Number(value))
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Pilih Store" />
                                    </SelectTrigger>

                                    <SelectContent
                                        align="end"
                                        className="max-h-80 overflow-y-auto"
                                    >
                                        {stores.map((store) => (
                                            <SelectItem
                                                key={store.id}
                                                value={String(store.id)}
                                            >
                                                {store.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button className="cursor-pointer">
                                <QrCode />
                                Scan
                            </Button>
                        </div>
                    </div>

                    <div className="mb-2 text-xs text-gray-500">
                        Menampilkan {filteredProducts.length} dari{' '}
                        {products.length} produk
                    </div>

                    <div className="flex-1 overflow-y-auto">
                        <ProductList
                            products={filteredProducts}
                            addToCart={addToCart}
                        />

                        {filteredProducts.length === 0 && (
                            <div className="rounded border border-dashed p-4 text-center text-gray-500">
                                Produk tidak ditemukan
                            </div>
                        )}
                    </div>
                </div>

                <div className="flex h-[calc(100vh-120px)] flex-col md:col-span-2 lg:col-span-3">
                    <div className="mb-2 rounded-md border">
                        <button
                            type="button"
                            onClick={() => setCustomerOpen(!customerOpen)}
                            className="flex w-full cursor-pointer items-center justify-between p-2 text-left"
                        >
                            <span className="text-sm font-medium">
                                {customerTitle}
                            </span>

                            <ChevronDown
                                className={`h-4 w-4 transition-transform ${
                                    customerOpen ? 'rotate-180' : ''
                                }`}
                            />
                        </button>

                        {customerOpen && (
                            <div className="border-t p-2">
                                <CustomerForm
                                    customers={customers}
                                    customer={customer}
                                    showCustomerResult={showCustomerResult}
                                    pointsUsed={pointsUsed}
                                    onPointsChange={setPointsUsed}
                                    onCustomerSearch={handleCustomerSearch}
                                    onCustomerSelect={(selected) => {
                                        setCustomer({
                                            id: selected.id,
                                            name: selected.name,
                                            phone: selected.phone ?? '',
                                            address: selected.address ?? '',
                                            current_point:
                                                selected.current_point ?? 0,
                                        });

                                        setPointsUsed(0);
                                        setShowCustomerResult(false);
                                    }}
                                    onCustomerChange={(field, value) =>
                                        setCustomer((prev) => ({
                                            ...prev,
                                            [field]: value,
                                        }))
                                    }
                                />
                            </div>
                        )}
                    </div>
                    <div className="mb-2 rounded-md border">
                        <button
                            type="button"
                            onClick={() => setPaymentOpen(!paymentOpen)}
                            className="flex w-full cursor-pointer items-center justify-between p-2 text-left"
                        >
                            <span className="text-sm font-medium">
                                {paymentTitle}
                            </span>

                            <ChevronDown
                                className={`h-4 w-4 transition-transform ${
                                    paymentOpen ? 'rotate-180' : ''
                                }`}
                            />
                        </button>

                        {paymentOpen && (
                            <div className="border-t p-2">
                                <DeliveryPaymentForm
                                    value={deliveryPayment}
                                    total={total}
                                    onChange={setDeliveryPayment}
                                />
                            </div>
                        )}
                    </div>

                    <Separator className="mb-2.5" />

                    <div className="mb-2 flex items-center justify-between rounded-md border p-2">
                        <span className="text-sm font-bold">Cart</span>

                        <span className="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium">
                            {cart.length} Produk
                        </span>
                    </div>
                    <div className="no-scrollbar flex-1 overflow-y-auto">
                        {cart.length > 0 ? (
                            cart.map((item, index) => (
                                <CartItemCard
                                    key={item.id}
                                    item={item}
                                    order={index + 1}
                                    onUpdateQty={updateQty}
                                    onUpdatePrice={updatePrice}
                                    onRemove={removeFromCart}
                                />
                            ))
                        ) : (
                            <div className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                                Belum ada produk di cart
                            </div>
                        )}
                    </div>

                    <div className="mt-3 border-t pt-3">
                        <div className="rounded-md border p-3 text-sm">
                            <div className="flex justify-between">
                                <span>Subtotal</span>
                                <span>{formatRupiah(subtotal)}</span>
                            </div>

                            <div className="flex justify-between">
                                <span>Ongkir</span>
                                <span>
                                    {formatRupiah(
                                        deliveryPayment.deliveryType ===
                                            'delivery'
                                            ? deliveryPayment.shippingCost
                                            : 0,
                                    )}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span>Potongan Point</span>
                                <span>-{formatRupiah(pointsUsed)}</span>
                            </div>

                            <div className="mt-2 flex justify-between border-t pt-2 font-bold">
                                <span>Total</span>
                                <span>{formatRupiah(total)}</span>
                            </div>
                        </div>

                        <Button
                            className="mt-4 w-full"
                            onClick={submit}
                            disabled={hasStockError || processing}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Menyimpan...
                                </>
                            ) : (
                                'Simpan Transaksi'
                            )}
                        </Button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
