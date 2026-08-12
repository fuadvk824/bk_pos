import { Head } from '@inertiajs/react';
import { useState } from 'react';
import type { VisibilityState } from '@tanstack/react-table';

import AppLayout from '@/layouts/app-layout';
import { DataTable } from '@/components/table/datatable';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { useTableActions } from '@/lib/useTableAction';

import { columnStores } from './column-store';
import { Store } from '@/types/custom/store';
import { PaginationMeta } from '@/types/custom/pagination';
import { useRoute } from '@/lib/route-ziggy';

interface Props {
    stores: {
        data: Store[];
        meta: PaginationMeta;
    };
    filters: {
        search?: string;
        year?: number;
        month?: number;
        perPage?: number;
    };
}

export default function Index({ stores, filters }: Props) {
    const route = useRoute();
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>(
        {},
    );

    const [localFilters, setLocalFilters] = useState({
        search: filters.search ?? '',
        year: filters.year ?? new Date().getFullYear(),
        month: filters.month ?? new Date().getMonth() + 1,
        perPage: filters.perPage ?? 10,
    });

    const { handleFilterChange } = useTableActions({
        filters: localFilters,
        indexRoute: 'store.index',
        exportRoute: '',
        allColumns: [],
    });

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Branch Store',
                    href: route('store.index'),
                },
            ]}
        >
            <Head title="Branch Store" />

            <div className="space-y-4 p-5">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Branch Store</h1>
                </div>

                <div className="grid grid-cols-1 gap-3 rounded-xl border p-4 md:grid-cols-3">
                    <div className="space-y-1">
                        <Label>Search</Label>

                        <Input
                            placeholder="Cari kode / nama store..."
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

                    <div className="space-y-1">
                        <Label>Tahun</Label>

                        <Input
                            type="number"
                            value={localFilters.year}
                            onChange={(e) =>
                                handleFilterChange(
                                    localFilters,
                                    setLocalFilters,
                                    'year',
                                    Number(e.target.value),
                                )
                            }
                        />
                    </div>

                    <div className="space-y-1">
                        <Label>Bulan</Label>

                        <Input
                            type="number"
                            min={1}
                            max={12}
                            value={localFilters.month}
                            onChange={(e) =>
                                handleFilterChange(
                                    localFilters,
                                    setLocalFilters,
                                    'month',
                                    Number(e.target.value),
                                )
                            }
                        />
                    </div>
                </div>

                <DataTable<Store>
                    columns={columnStores}
                    data={stores.data}
                    meta={stores.meta}
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
            </div>
        </AppLayout>
    );
}
