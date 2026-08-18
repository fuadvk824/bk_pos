import { Head, router, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import QRCode from 'react-qr-code';

import AppLayout from '@/layouts/app-layout';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
} from '@/components/ui/dialog';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Separator } from '@/components/ui/separator';

import { ProductDetail } from '@/types/custom/product-detail';
import { formatRupiah } from '@/lib/format-rupiah';
import { useRoute } from '@/lib/route-ziggy';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import { ChevronDown, ChevronUp, Pencil, ArrowRightLeft } from 'lucide-react';

interface Store {
    id: number;
    store_code: string;
    name: string;
}

interface Props {
    product: ProductDetail;
    stores: Store[];
}

export default function Show({ product, stores }: Props) {
    const route = useRoute();

    const productStores = product.stores ?? [];
    const [openEdit, setOpenEdit] = useState(false);
    const { data, setData, post, processing } = useForm({
        description: product.description ?? '',
        image: null as File | null,
        _method: 'put',
    });

    const [errors, setErrors] = useState<any>({});

    const [expanded, setExpanded] = useState(false);
    const [scrollDir, setScrollDir] = useState<'up' | 'down'>('down');
    const [lastScrollTop, setLastScrollTop] = useState(0);
    const containerRef = useRef<HTMLDivElement | null>(null);

    const handleScroll = () => {
        const el = containerRef.current;
        if (!el) {
            return;
        }

        const current = el.scrollTop;
        if (current > lastScrollTop) {
            setScrollDir('down');
        } else {
            setScrollDir('up');
        }
        setLastScrollTop(current);
    };

    const isDataChanged = () => {
        return (
            data.description !== (product.description ?? '') ||
            data.image !== null
        );
    };

    const validate = () => {
        const err: any = {};

        if (!data.description) {
            err.description = 'Deskripsi wajib diisi';
        }
        setErrors(err);
        return Object.keys(err).length === 0;
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!validate()) {
            toast.error('Mohon lengkapi data');

            return;
        }

        if (!isDataChanged()) {
            toast.warning('Tidak ada perubahan');
            return;
        }

        post(route('product.update', product.id), {
            forceFormData: true,

            onSuccess: (page: any) => {
                const flash = page?.props?.flash;

                if (flash?.success) {
                    toast.success(flash.success);
                } else {
                    toast.success('Berhasil update produk');
                }

                setOpenEdit(false);
            },

            onError: (err: any) => {
                setErrors(err);
                toast.error('Gagal update');
            },
        });
    };

    const [openTransfer, setOpenTransfer] = useState(false);

    const {
        data: transferData,
        setData: setTransferData,
        put: transfer,
        processing: transferProcessing,
        errors: transferErrors,
        reset: resetTransfer,
    } = useForm({
        source_store_id: '',
        destination_store_id: '',
        quantity: 1,
    });

    const getProductStore = (storeId: string) => {
        return productStores.find(
            (store: any) =>
                String(store.id) === storeId ||
                String(store.store_id) === storeId,
        );
    };

    const selectedSourceStore = transferData.source_store_id
        ? getProductStore(transferData.source_store_id)
        : null;

    const availableStock = Number(selectedSourceStore?.stock ?? 0);

    const submitTransfer = (e: React.FormEvent) => {
        e.preventDefault();

        if (!transferData.source_store_id) {
            toast.error('Pilih store asal.');
            return;
        }

        if (!transferData.destination_store_id) {
            toast.error('Pilih store tujuan.');
            return;
        }

        if (
            transferData.source_store_id === transferData.destination_store_id
        ) {
            toast.error('Store asal dan tujuan tidak boleh sama.');
            return;
        }

        const qty = Number(transferData.quantity);

        if (!qty || qty <= 0) {
            toast.error('Quantity harus lebih dari 0.');
            return;
        }

        if (qty > availableStock) {
            toast.error(
                `Stock tidak cukup. Stock tersedia: ${availableStock}.`,
            );
            return;
        }

        transfer(route('product.transfer-stock', product.id), {
            preserveScroll: true,

            onSuccess: (page: any) => {
                const flash = page?.props?.flash;
                toast.success(flash?.success ?? 'Stock berhasil dipindahkan.');
                setOpenTransfer(false);
                resetTransfer();
                router.reload({
                    only: ['product'],
                });
            },

            onError: (errors) => {
                const message = errors.transfer ?? 'Gagal memindahkan stock.';
                toast.error(message);
            },
        });
    };

    const handleOpenTransfer = () => {
        resetTransfer();

        setOpenTransfer(true);
    };

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Product',
                    href: '/product',
                },
                {
                    title: product.name,
                    href: '#',
                },
            ]}
        >
            <Head title={product.name} />

            <div className="space-y-6 p-5 pb-20">
                <div className="flex items-start justify-between">
                    <div className="flex w-full gap-6">
                        <div>
                            {product.image ? (
                                <img
                                    src={`/storage/${product.image}`}
                                    className="h-64 w-64 rounded-xl border object-cover"
                                    alt={product.name}
                                />
                            ) : (
                                <div className="flex h-64 w-64 items-center justify-center rounded-xl bg-gray-100">
                                    No Image
                                </div>
                            )}

                            {product.barcode && (
                                <div className="mt-4 w-full rounded-lg border p-3">
                                    <div className="mb-2 border-b border-black text-xs font-semibold">
                                        <h1>{product.name}</h1>
                                    </div>

                                    <div className="flex items-stretch justify-between gap-3">
                                        <div className="flex flex-1 flex-col justify-between">
                                            <div className="flex flex-col leading-tight">
                                                <span className="text-[10px] text-gray-400">
                                                    Harga Normal:
                                                </span>

                                                <span className="text-sm font-semibold">
                                                    Rp. 287.000
                                                </span>
                                            </div>

                                            <div className="flex flex-col leading-tight">
                                                <span className="text-[10px] text-gray-400">
                                                    Harga Promo:
                                                </span>

                                                <span className="text-sm font-semibold">
                                                    Rp. 250.000
                                                </span>
                                            </div>
                                        </div>

                                        <div className="aspect-square h-auto self-stretch">
                                            <QRCode
                                                value={product.barcode}
                                                size={75}
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="w-full space-y-2">
                            <div className="flex items-center justify-between">
                                <h1 className="text-xl font-bold">
                                    {product.name}
                                </h1>

                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={handleOpenTransfer}
                                        size="sm"
                                        className="cursor-pointer hover:scale-105"
                                    >
                                        <ArrowRightLeft className="h-4 w-4" />
                                        Mutasi
                                    </Button>

                                    <Button
                                        type="button"
                                        onClick={() => setOpenEdit(true)}
                                        size="sm"
                                        className="cursor-pointer hover:scale-105"
                                    >
                                        <Pencil className="h-4 w-4" />
                                        Edit
                                    </Button>
                                </div>
                            </div>

                            <div>
                                <span className="text-xs text-gray-500">
                                    {product.product_code}
                                </span>

                                <div className="text-xs">
                                    <span className="font-medium">
                                        Kategori:
                                    </span>{' '}
                                    {product.category}
                                </div>

                                <span className="text-xs font-medium">
                                    Deskripsi:
                                </span>

                                <div className="relative mt-2">
                                    <div
                                        ref={containerRef}
                                        onScroll={handleScroll}
                                        className={`no-scrollbar overflow-y-auto transition-all duration-300 ${
                                            expanded
                                                ? 'max-h-none'
                                                : 'fade-mask max-h-72'
                                        }`}
                                    >
                                        <div className="pb-12 text-xs leading-snug whitespace-pre-line text-gray-700">
                                            {product.description}
                                        </div>
                                    </div>

                                    {!expanded && (
                                        <div className="pointer-events-none absolute bottom-0 left-0 h-16 w-full bg-white" />
                                    )}

                                    <div
                                        className={`absolute left-1/2 -translate-x-1/2 transition-all duration-300 ${
                                            expanded ? 'bottom-2' : 'bottom-3'
                                        }`}
                                    >
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setExpanded(!expanded);

                                                if (expanded) {
                                                    containerRef.current?.scrollTo(
                                                        {
                                                            top: 0,
                                                            behavior: 'smooth',
                                                        },
                                                    );
                                                }
                                            }}
                                            className="flex cursor-pointer items-center gap-1 rounded-full bg-white px-3 py-1 text-xs shadow transition hover:scale-105"
                                        >
                                            {expanded ? (
                                                <ChevronUp size={14} />
                                            ) : scrollDir === 'down' ? (
                                                <ChevronDown size={14} />
                                            ) : (
                                                <ChevronUp size={14} />
                                            )}

                                            {expanded ? 'Tutup' : 'Lihat'}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <Tabs defaultValue="store">
                    <TabsList>
                        <TabsTrigger value="store">Stok per Toko</TabsTrigger>
                    </TabsList>

                    <TabsContent value="store">
                        <div className="rounded-xl border p-4">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left">
                                        <th className="p-2">Toko</th>
                                        <th className="p-2">Stock</th>
                                        <th className="p-2">Harga</th>
                                        <th className="p-2">Diskon</th>
                                    </tr>
                                </thead>

                                <tbody className="text-xs">
                                    {productStores.length > 0 ? (
                                        productStores.map((store: any) => (
                                            <tr
                                                key={store.id}
                                                className="border-b"
                                            >
                                                <td className="p-2">
                                                    {store.name}
                                                </td>
                                                <td className="p-2">
                                                    {store.stock}
                                                </td>
                                                <td className="p-2">
                                                    {formatRupiah(store.price)}
                                                </td>
                                                <td className="p-2 text-red-500">
                                                    {formatRupiah(
                                                        store.discount,
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="p-4 text-center text-gray-400"
                                            >
                                                Tidak ada data store
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>

            <Dialog open={openEdit} onOpenChange={setOpenEdit}>
                <DialogContent className="w-full max-w-lg">
                    <div className="overflow-hidden">
                        <DialogHeader className="mb-3">
                            <DialogTitle>Edit Product</DialogTitle>

                            <DialogDescription className="font-normal">
                                Silahkan ubah data produk
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <Label>Deskripsi</Label>

                                <Textarea
                                    value={data.description}
                                    onChange={(e) =>
                                        setData('description', e.target.value)
                                    }
                                    rows={5}
                                    placeholder="Masukkan deskripsi produk..."
                                    className={`h-44 w-full resize-none overflow-y-auto ${
                                        errors.description && 'border-red-500'
                                    }`}
                                />

                                {errors.description && (
                                    <p className="text-xs text-red-500">
                                        {errors.description}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label>Gambar</Label>

                                <Input
                                    type="file"
                                    onChange={(e) =>
                                        setData(
                                            'image',
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                />
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpenEdit(false)}
                                >
                                    Batal
                                </Button>

                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Menyimpan...' : 'Simpan'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog open={openTransfer} onOpenChange={setOpenTransfer}>
                <DialogContent className="w-full max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <ArrowRightLeft className="h-5 w-5" />
                            Mutasi Produk
                        </DialogTitle>

                        <DialogDescription>
                            Mutasi <strong className='underline'>{product.name}</strong> dari
                            satu store ke store lainnya.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitTransfer} className="space-y-5">
                        <div className="space-y-2">
                            <Label htmlFor="source_store_id">Store Asal</Label>

                            <Select
                                value={transferData.source_store_id}
                                onValueChange={(value) => {
                                    setTransferData('source_store_id', value);
                                    setTransferData('destination_store_id', '');
                                    setTransferData('quantity', 1);
                                }}
                            >
                                <SelectTrigger
                                    id="source_store_id"
                                    className={
                                        transferErrors.source_store_id
                                            ? 'border-red-500'
                                            : ''
                                    }
                                >
                                    <SelectValue placeholder="Pilih store asal" />
                                </SelectTrigger>

                                <SelectContent align="start">
                                    {productStores
                                        .filter(
                                            (store: any) =>
                                                Number(store.stock ?? 0) > 0,
                                        )
                                        .map((store: any) => (
                                            <SelectItem
                                                key={store.id}
                                                value={String(store.id)}
                                            >
                                                <div className="flex w-full items-center justify-between gap-6">
                                                    <span>{store.name}</span>

                                                    <span className="text-xs text-muted-foreground">
                                                        Stok: {store.stock}
                                                    </span>
                                                </div>
                                            </SelectItem>
                                        ))}
                                </SelectContent>
                            </Select>

                            {transferErrors.source_store_id && (
                                <p className="text-xs text-red-500">
                                    {transferErrors.source_store_id}
                                </p>
                            )}

                            {selectedSourceStore && (
                                <div className="flex items-center justify-between rounded-lg border bg-muted/40 px-3 py-2">
                                    <div className="flex flex-col">
                                        <span className="text-xs text-muted-foreground">
                                            Stock saat ini
                                        </span>

                                        <span className="text-sm font-semibold">
                                            {selectedSourceStore.name}
                                        </span>
                                    </div>

                                    <span className="text-lg font-bold">
                                        {availableStock}
                                    </span>
                                </div>
                            )}
                        </div>

                        <div className="relative flex items-center justify-center">
                            <Separator />

                            <div className="absolute rounded-full border bg-background p-2 shadow-sm">
                                <ArrowRightLeft className="h-4 w-4 text-muted-foreground" />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="destination_store_id">
                                Store Tujuan
                            </Label>

                            <Select
                                value={transferData.destination_store_id}
                                onValueChange={(value) =>
                                    setTransferData(
                                        'destination_store_id',
                                        value,
                                    )
                                }
                                disabled={!transferData.source_store_id}
                            >
                                <SelectTrigger
                                    id="destination_store_id"
                                    className={
                                        transferErrors.destination_store_id
                                            ? 'border-red-500'
                                            : ''
                                    }
                                >
                                    <SelectValue
                                        placeholder={
                                            transferData.source_store_id
                                                ? 'Pilih store tujuan'
                                                : 'Pilih store asal terlebih dahulu'
                                        }
                                    />
                                </SelectTrigger>

                                <SelectContent align="start">
                                    {stores
                                        .filter(
                                            (store) =>
                                                String(store.id) !==
                                                transferData.source_store_id,
                                        )
                                        .map((store) => {
                                            const productStore =
                                                getProductStore(
                                                    String(store.id),
                                                );

                                            const stock = Number(
                                                productStore?.stock ?? 0,
                                            );

                                            return (
                                                <SelectItem
                                                    key={store.id}
                                                    value={String(store.id)}
                                                >
                                                    <div className="flex w-full items-center justify-between gap-6">
                                                        <span>
                                                            {store.name}
                                                        </span>

                                                        <span className="text-xs text-muted-foreground">
                                                            Stok: {stock}
                                                        </span>
                                                    </div>
                                                </SelectItem>
                                            );
                                        })}
                                </SelectContent>
                            </Select>

                            {transferErrors.destination_store_id && (
                                <p className="text-xs text-red-500">
                                    {transferErrors.destination_store_id}
                                </p>
                            )}

                            {transferData.destination_store_id && (
                                <div className="flex items-center justify-between rounded-lg border bg-muted/40 px-3 py-2">
                                    <div className="flex flex-col">
                                        <span className="text-xs text-muted-foreground">
                                            Stock saat ini
                                        </span>

                                        <span className="text-sm font-semibold">
                                            {getProductStore(
                                                transferData.destination_store_id,
                                            )?.name ??
                                                stores.find(
                                                    (store) =>
                                                        String(store.id) ===
                                                        transferData.destination_store_id,
                                                )?.name}
                                        </span>
                                    </div>

                                    <span className="text-lg font-bold">
                                        {Number(
                                            getProductStore(
                                                transferData.destination_store_id,
                                            )?.stock ?? 0,
                                        )}
                                    </span>
                                </div>
                            )}
                        </div>

                        <Separator />

                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="transfer_quantity">
                                    Quantity
                                </Label>

                                {selectedSourceStore && (
                                    <span className="text-xs text-muted-foreground">
                                        Maksimal {availableStock}
                                    </span>
                                )}
                            </div>

                            <Input
                                id="transfer_quantity"
                                type="number"
                                min={1}
                                max={availableStock || undefined}
                                value={transferData.quantity}
                                disabled={!transferData.source_store_id}
                                className="[appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                onChange={(e) => {
                                    const value = Number(e.target.value);

                                    if (!transferData.source_store_id) {
                                        return;
                                    }

                                    if (value > availableStock) {
                                        setTransferData(
                                            'quantity',
                                            availableStock,
                                        );
                                        return;
                                    }

                                    if (value < 1 || Number.isNaN(value)) {
                                        setTransferData('quantity', 1);
                                        return;
                                    }

                                    setTransferData('quantity', value);
                                }}
                            />

                            {transferErrors.quantity && (
                                <p className="text-xs text-red-500">
                                    {transferErrors.quantity}
                                </p>
                            )}
                        </div>

                        {transferData.source_store_id &&
                            transferData.destination_store_id &&
                            Number(transferData.quantity) > 0 && (
                                <div className="rounded-xl border bg-muted/30 p-4">
                                    <div className="mb-3 text-xs font-medium text-muted-foreground">
                                        Ringkasan Transfer
                                    </div>

                                    <div className="flex items-center justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-semibold">
                                                {selectedSourceStore?.name}
                                            </p>

                                            <p className="text-xs text-muted-foreground">
                                                Stock: {availableStock}
                                            </p>
                                        </div>

                                        <ArrowRightLeft className="h-4 w-4 shrink-0 text-muted-foreground" />

                                        <div className="min-w-0 text-right">
                                            <p className="truncate text-sm font-semibold">
                                                {getProductStore(
                                                    transferData.destination_store_id,
                                                )?.name ??
                                                    stores.find(
                                                        (store) =>
                                                            String(store.id) ===
                                                            transferData.destination_store_id,
                                                    )?.name}
                                            </p>

                                            <p className="text-xs text-muted-foreground">
                                                Stock:{' '}
                                                {Number(
                                                    getProductStore(
                                                        transferData.destination_store_id,
                                                    )?.stock ?? 0,
                                                )}
                                            </p>
                                        </div>
                                    </div>

                                    <Separator className="my-3" />

                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-muted-foreground">
                                            Jumlah dipindahkan
                                        </span>

                                        <span className="text-lg font-bold">
                                            {Number(transferData.quantity)}
                                        </span>
                                    </div>

                                    <div className="mt-2 flex items-center justify-between">
                                        <span className="text-sm text-muted-foreground">
                                            Sisa stock asal
                                        </span>

                                        <span className="text-sm font-semibold">
                                            {Math.max(
                                                0,
                                                availableStock -
                                                    Number(
                                                        transferData.quantity,
                                                    ),
                                            )}
                                        </span>
                                    </div>
                                </div>
                            )}

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenTransfer(false)}
                                disabled={transferProcessing}
                            >
                                Batal
                            </Button>

                            <Button
                                type="submit"
                                disabled={
                                    transferProcessing ||
                                    !transferData.source_store_id ||
                                    !transferData.destination_store_id ||
                                    Number(transferData.quantity) <= 0 ||
                                    Number(transferData.quantity) >
                                        availableStock
                                }
                            >
                                {transferProcessing
                                    ? 'Memproses...'
                                    : 'Transfer Stock'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
