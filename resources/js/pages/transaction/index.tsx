import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import AppLayout from '@/layouts/app-layout';
import { DataTable } from '@/components/table/datatable';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

import { VisibilityState } from '@tanstack/react-table';
import { FileSpreadsheet, RefreshCw } from 'lucide-react';

import { useRoute } from '@/lib/route-ziggy';
import { useTableActions } from '@/lib/useTableAction';

import { columnTransactions } from './column-transaction';
import { Transaction } from '@/types/custom/transaction';
import { PaginationMeta } from '@/types/custom/pagination';
import ProcessTransactionDialog from './process-transaction-dialog';

interface Props {
    transactions: {
        data: Transaction[];
        meta: PaginationMeta;
    };

    filters: {
        search?: string;
        perPage?: number;
    };
}

export default function Index({ transactions, filters }: Props) {
    const route = useRoute();
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>(
        {},
    );
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [selectedTransaction, setSelectedTransaction] =
        useState<Transaction | null>(null);

    const [openProcess, setOpenProcess] = useState(false);

    const allColumns = [
        'invoice_number',
        'customer_name',
        'customer_address',
        'store_name',
        'total',
        'payment_status',
        'created_at',
    ];

    const [localFilters, setLocalFilters] = useState({
        search: filters.search ?? '',
        perPage: filters.perPage ?? 10,
    });

    const { handleFilterChange, handleExport } = useTableActions({
        filters: localFilters,
        indexRoute: 'transaction.index',
        exportRoute: 'transaction.export',
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
            route('transaction.index'),
            {},
            {
                replace: true,
                onFinish: () => setIsRefreshing(false),
            },
        );
    };

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Transaction',
                    href: route('transaction.index'),
                },
            ]}
        >
            <Head title="Transaction" />

            <div className="space-y-4 p-5">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Data Transaction</h1>

                    <div className="flex gap-2">
                        <Button variant="outline" onClick={handleResetFilters}>
                            <RefreshCw
                                className={`h-4 w-4 ${
                                    isRefreshing ? 'animate-spin' : ''
                                }`}
                            />
                            Refresh
                        </Button>

                        <Button
                            variant="outline"
                            onClick={() => handleExport(columnVisibility)}
                        >
                            <FileSpreadsheet className="h-4 w-4" />
                            Export
                        </Button>
                    </div>
                </div>

                <div className="rounded-xl border p-4">
                    <div className="space-y-1">
                        <Label>Search</Label>

                        <Input
                            placeholder="Cari invoice atau customer..."
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

                <DataTable<Transaction>
                    columns={columnTransactions((transaction) => {
                        setSelectedTransaction(transaction);
                        setOpenProcess(true);
                    })}
                    data={transactions.data}
                    meta={transactions.meta}
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

                <ProcessTransactionDialog
                    key={selectedTransaction?.id}
                    open={openProcess}
                    onOpenChange={setOpenProcess}
                    transaction={selectedTransaction}
                />
            </div>
        </AppLayout>
    );
}
