import { Head } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
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

import { ProductDetail } from '@/types/custom/product-detail';
import { formatRupiah } from '@/lib/format-rupiah';
import { useRoute } from '@/lib/route-ziggy';
import { ChevronDown, ChevronUp, Pencil } from 'lucide-react';
import { Textarea } from '@/components/ui/textarea';
import { Separator } from '@/components/ui/separator';

interface Props {
    product: ProductDetail;
}
export default function Show({ product }: Props) {
    const route = useRoute();
    const stores = product.stores ?? [];
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
        if (!el) return;

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

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Product', href: '/product' },
                { title: product.name, href: '#' },
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
                                <Button
                                    onClick={() => setOpenEdit(true)}
                                    size="sm"
                                    className="cursor-pointer hover:scale-105"
                                >
                                    <Pencil /> Edit
                                </Button>
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
                                        <div className="pointer-events-none absolute bottom-0 left-0 h-16 w-full bg-gradient-to-t from-white to-transparent" />
                                    )}

                                    <div
                                        className={`absolute left-1/2 -translate-x-1/2 transition-all duration-300 ${
                                            expanded ? 'bottom-2' : 'bottom-3'
                                        }`}
                                    >
                                        <button
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
                                    {stores.length > 0 ? (
                                        stores.map((store) => (
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
                            <DialogTitle>
                                Edit Product
                                <DialogDescription className="font-normal">
                                    Silahkan ubah data produk
                                </DialogDescription>
                            </DialogTitle>
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
        </AppLayout>
    );
}
