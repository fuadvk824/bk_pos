import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import AppLayout from '@/layouts/app-layout';
import { DataTable } from '@/components/table/datatable';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

import { columnProducts } from './column-product';
import { Product } from '@/types/custom/product';
import { PaginationMeta } from '@/types/custom/pagination';
import { VisibilityState } from '@tanstack/react-table';
import { useTableActions } from '@/lib/useTableAction';
import { FileSpreadsheet, RefreshCw } from 'lucide-react';
import { useRoute } from '@/lib/route-ziggy';

interface Props {
    products: {
        data: Product[];
        meta: PaginationMeta;
    };
    filters: {
        search?: string;
        perPage?: number;
    };
}

export default function Index({ products, filters }: Props) {
    const route = useRoute();
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>(
        {},
    );
    const [isRefreshing, setIsRefreshing] = useState(false);

    const allColumns = ['id', 'product_code', 'name', 'category', 'stock_all'];

    const [localFilters, setLocalFilters] = useState({
        search: filters.search ?? '',
        perPage: filters.perPage ?? 10,
    });

    const { handleFilterChange, handleExport } = useTableActions({
        filters: localFilters,
        indexRoute: 'product.index',
        exportRoute: 'product.export',
        allColumns,
    });

    const handleResetFilters = () => {
        setIsRefreshing(true);
        const defaultFilters = {
            search: '',
            perPage: 10,
        };

        setLocalFilters(defaultFilters);

        router.get(
            route('product.index'),
            {},
            {
                replace: true,
                onFinish: () => setIsRefreshing(false),
            },
        );
    };

    return (
        <AppLayout
            breadcrumbs={[{ title: 'Product', href: route('product.index') }]}
        >
            <Head title="Product" />

            <div className="space-y-4 p-5">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Data Products</h1>

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
                        >
                            <FileSpreadsheet className="h-4 w-4" />
                            Export
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-3 rounded-xl border p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div className="space-y-1">
                        <Label className="text-[11px]">Search</Label>
                        <Input
                            placeholder="Cari produk..."
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

                <DataTable<Product>
                    columns={columnProducts()}
                    data={products.data}
                    meta={products.meta}
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
