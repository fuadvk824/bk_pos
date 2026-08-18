import { useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { router, useForm } from '@inertiajs/react';

import { formatRupiah } from '@/lib/format-rupiah';
import { Store } from '@/types/custom/store';
import { useRoute } from '@/lib/route-ziggy';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const monthNames = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

function TargetDialog({ store }: { store: Store }) {
    const route = useRoute();

    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth() + 1;

    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        year: store.target?.year ?? currentYear,
        month: store.target?.month ?? currentMonth,
        target_amount: Number(store.target?.target_amount ?? 0),
        status: store.target?.status ?? ('inactive' as 'active' | 'inactive'),
    });

    const submit = () => {
        post(route('store.target.store', store.id), {
            preserveScroll: true,

            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };

    const handleAmountChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const raw = e.target.value.replace(/\D/g, '');
        const amount = raw ? Number(raw) : 0;

        setData('target_amount', amount);
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(value) => {
                setOpen(value);

                if (!value) {
                    reset();
                }
            }}
        >
            <DialogTrigger asChild>
                <Button size="sm">
                    {store.target ? 'Edit Target' : 'Tambah Target'}
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Target - {store.name}</DialogTitle>

                    <DialogDescription>
                        Atur target penjualan untuk store ini.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label>Tahun</Label>
                        <Input
                            type="number"
                            value={data.year}
                            onChange={(e) =>
                                setData('year', Number(e.target.value))
                            }
                        />

                        {errors.year && (
                            <p className="text-sm text-red-500">
                                {errors.year}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label>Bulan</Label>
                        <Select
                            value={data.month.toString()}
                            onValueChange={(value) =>
                                setData('month', Number(value))
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih bulan" />
                            </SelectTrigger>

                            <SelectContent align="start">
                                {monthNames.map((name, index) => (
                                    <SelectItem
                                        key={index + 1}
                                        value={(index + 1).toString()}
                                    >
                                        {name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        {errors.month && (
                            <p className="text-sm text-red-500">
                                {errors.month}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label>Target Amount</Label>
                        <Input
                            type="text"
                            inputMode="numeric"
                            value={formatRupiah(String(data.target_amount))}
                            onFocus={(e) => e.target.select()}
                            onChange={handleAmountChange}
                        />

                        {errors.target_amount && (
                            <p className="text-sm text-red-500">
                                {errors.target_amount}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label>Status</Label>
                        <Select
                            value={data.status}
                            onValueChange={(value) =>
                                setData(
                                    'status',
                                    value as 'active' | 'inactive',
                                )
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent align="start">
                                <SelectItem value="active">Active</SelectItem>

                                <SelectItem value="inactive">
                                    Inactive
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        {errors.status && (
                            <p className="text-sm text-red-500">
                                {errors.status}
                            </p>
                        )}
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => setOpen(false)}>
                        Batal
                    </Button>

                    <Button onClick={submit} disabled={processing}>
                        {processing ? 'Menyimpan...' : 'Simpan'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export const columnStores: ColumnDef<Store>[] = [
    {
        accessorKey: 'store_code',
        header: 'Kode Store',
    },

    {
        accessorKey: 'name',
        header: 'Nama Store',
    },

    {
        accessorKey: 'address',
        header: 'Alamat',
        cell: ({ row }) => (
            <div className="max-w-56 truncate">
                {row.getValue('address') ?? '-'}
            </div>
        ),
    },

    {
        accessorKey: 'target_amount',
        header: 'Target',
        cell: ({ row }) => {
            const amount = row.original.target?.target_amount;

            if (!amount) return '-';

            return formatRupiah(Number(amount));
        },
    },

    {
        id: 'active_period',
        header: 'Aktif Mulai',
        cell: ({ row }) => {
            const target = row.original.target;

            if (!target) return '-';

            return `${monthNames[target.month - 1]} ${target.year}`;
        },
    },

    {
        id: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.original.target?.status;

            if (!status) return '-';

            return status === 'active' ? 'Active' : 'Inactive';
        },
    },

    {
        id: 'actions',
        header: 'Action',
        cell: ({ row }) => <TargetDialog store={row.original} />,
    },
];
