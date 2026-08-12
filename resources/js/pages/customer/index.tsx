import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import type { VisibilityState } from '@tanstack/react-table';

import AppLayout from '@/layouts/app-layout';
import { DataTable } from '@/components/table/datatable';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { useTableActions } from '@/lib/useTableAction';

import { PaginationMeta } from '@/types/custom/pagination';
import { useRoute } from '@/lib/route-ziggy';
import { Customer } from '@/types/custom/customer';
import { columnCustomers } from './column-customer';
import { Button } from '@/components/ui/button';
import { FileSpreadsheet, RefreshCw } from 'lucide-react';
import { PointSetting } from '@/types/custom/point-setting';

import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
} from '@/components/ui/dialog';

import { Save, Settings } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { formatRupiah } from '@/lib/format-rupiah';
import { toast } from 'sonner';

interface Props {
    customers: {
        data: Customer[];
        meta: PaginationMeta;
    };
    pointSetting: PointSetting;
    filters: {
        search?: string;
        perPage?: number;
    };
}

export default function Index({ customers, filters, pointSetting }: Props) {
    const route = useRoute();
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>(
        {},
    );
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [openPointSetting, setOpenPointSetting] = useState(false);

    const { data, setData, put, processing } = useForm({
        spend_amount: pointSetting.spend_amount,
        point_reward: pointSetting.point_reward,
        minimum_transaction: pointSetting.minimum_transaction,
    });

    const [localFilters, setLocalFilters] = useState({
        search: filters.search ?? '',
        perPage: filters.perPage ?? 10,
    });

    const { handleFilterChange, handleExport } = useTableActions({
        filters: localFilters,
        indexRoute: 'customer.index',
        exportRoute: 'customer.export',
        allColumns: [],
    });

    const handleResetFilters = () => {
        setIsRefreshing(true);
        const defaultFilters = {
            search: '',
            perPage: 10,
        };

        setLocalFilters(defaultFilters);

        router.get(
            route('customer.index'),
            {},
            {
                replace: true,
                onFinish: () => setIsRefreshing(false),
            },
        );
    };

    const handleUpdatePointSetting = () => {
        put(route('customer.point.update', pointSetting.id), {
            preserveScroll: true,
            onSuccess: () => {
                setOpenPointSetting(false);
                toast.success('Perubahan point customer berhasil');
            },
        });
    };

    const resetPointSettingForm = () => {
        setData({
            spend_amount: pointSetting.spend_amount,
            point_reward: pointSetting.point_reward,
            minimum_transaction: pointSetting.minimum_transaction,
        });
    };

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Customer',
                    href: route('customer.index'),
                },
            ]}
        >
            <Head title="Customer" />

            <div className="space-y-4 p-5">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Customer</h1>

                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            onClick={handleResetFilters}
                            className="cursor-pointer"
                        >
                            <RefreshCw
                                className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`}
                            />
                            Refresh
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => handleExport(columnVisibility)}
                            disabled
                            className="cursor-pointer"
                        >
                            <FileSpreadsheet className="h-4 w-4" />
                            Export
                        </Button>
                        <Button
                            variant="default"
                            onClick={() => setOpenPointSetting(true)}
                            className="cursor-pointer"
                        >
                            <Settings className="h-4 w-4" />
                            Point Setting
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-3 rounded-xl border p-4 md:grid-cols-3">
                    <div className="space-y-1">
                        <Label>Search</Label>

                        <Input
                            placeholder="Cari nama / no telp..."
                            value={localFilters.search}
                            onChange={(e) =>
                                handleFilterChange(
                                    localFilters,
                                    setLocalFilters,
                                    'search',
                                    e.target.value,
                                )
                            }
                        />
                    </div>
                </div>

                <DataTable<Customer>
                    columns={columnCustomers}
                    data={customers.data}
                    meta={customers.meta}
                    columnVisibility={columnVisibility}
                    onColumnVisibilityChange={setColumnVisibility}
                    perPage={localFilters.perPage}
                    onPerPageChange={(value) =>
                        handleFilterChange(
                            localFilters,
                            setLocalFilters,
                            'perPage',
                            value,
                        )
                    }
                />

                <Dialog
                    open={openPointSetting}
                    onOpenChange={(open) => {
                        setOpenPointSetting(open);

                        if (!open) {
                            resetPointSettingForm();
                        }
                    }}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Point Setting</DialogTitle>
                            <DialogDescription>
                                Atur nominal belanja dan point reward yang
                                didapatkan
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4">
                            <div>
                                <Label>Belanja (Rp)</Label>
                                <Input
                                    type="text"
                                    value={formatRupiah(data.spend_amount || 0)}
                                    onChange={(e) => {
                                        const raw = e.target.value.replace(
                                            /[^0-9]/g,
                                            '',
                                        );

                                        setData(
                                            'spend_amount',
                                            raw === '' ? 0 : Number(raw),
                                        );
                                    }}
                                />
                            </div>

                            <div>
                                <Label>Reward Point</Label>

                                <Input
                                    type="text"
                                    value={data.point_reward || 0}
                                    onChange={(e) => {
                                        const raw = e.target.value.replace(
                                            /[^0-9]/g,
                                            '',
                                        );

                                        setData(
                                            'point_reward',
                                            raw === '' ? 0 : Number(raw),
                                        );
                                    }}
                                />
                            </div>

                            <div>
                                <Label>Minimum Transaksi</Label>

                                <Input
                                    type="text"
                                    value={formatRupiah(
                                        data.minimum_transaction || 0,
                                    )}
                                    onChange={(e) => {
                                        const raw = e.target.value.replace(
                                            /[^0-9]/g,
                                            '',
                                        );

                                        setData(
                                            'minimum_transaction',
                                            raw === '' ? 0 : Number(raw),
                                        );
                                    }}
                                />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => {
                                    resetPointSettingForm();
                                    setOpenPointSetting(false);
                                }}
                            >
                                Batal
                            </Button>

                            <Button
                                onClick={handleUpdatePointSetting}
                                disabled={processing}
                            >
                                <Save className="h-4 w-4" />
                                Simpan
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
