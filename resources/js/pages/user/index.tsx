import { Head, router } from '@inertiajs/react';
import type { VisibilityState } from '@tanstack/react-table';
import { useState } from 'react';

import { DataTable } from '@/components/table/datatable';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';

import { useTableActions } from '@/lib/useTableAction';
import { useRoute } from '@/lib/route-ziggy';

import { Cog, FileSpreadsheet, RefreshCw } from 'lucide-react';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import Form from './form';

import type { User } from '@/types/custom/user';
import type { PaginationMeta } from '@/types/custom/pagination';
import { columnUsers } from './column-user';

interface Option {
    id: number;
    name: string;
}

interface Props {
    users: {
        data: User[];
        meta: PaginationMeta;
    };
    filters: {
        search?: string;
        store_id?: number;
        perPage?: number;
    };
    stores: Option[];
}

export default function Index({ users, filters, stores }: Props) {
    const route = useRoute();

    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>(
        {},
    );

    const [isRefreshing, setIsRefreshing] = useState(false);

    const allColumns = ['name', 'email', 'username', 'store'];

    const [localFilters, setLocalFilters] = useState({
        search: filters.search ?? '',
        store_id: filters.store_id ?? undefined,
        perPage: filters.perPage ?? 10,
    });

    const { handleFilterChange, handleExport } = useTableActions({
        filters: localFilters,
        indexRoute: 'user.index',
        exportRoute: 'user.export',
        allColumns,
    });

    const handleResetFilters = () => {
        setIsRefreshing(true);

        const defaultFilters = {
            search: '',
            store_id: undefined,
            perPage: 10,
        };

        setLocalFilters(defaultFilters);

        router.get(
            route('user.index'),
            {},
            {
                replace: true,
                preserveState: false,
                preserveScroll: true,
                onFinish: () => setIsRefreshing(false),
            },
        );
    };

    const [open, setOpen] = useState(false);
    const [selectedUser, setSelectedUser] = useState<User | null>(null);

    const openCreate = () => {
        setSelectedUser(null);
        setOpen(true);
    };

    const openEdit = (user: User) => {
        setSelectedUser(user);
        setOpen(true);
    };

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'User',
                    href: route('user.index'),
                },
            ]}
        >
            <Head title="User" />

            <div className="space-y-4 p-5">
                {/* HEADER */}
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Data User</h1>

                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            onClick={handleResetFilters}
                            className="cursor-pointer"
                        >
                            <RefreshCw
                                className={`h-4 w-4 ${
                                    isRefreshing ? 'animate-spin' : ''
                                }`}
                            />

                            <span className="hidden sm:block">Refresh</span>
                        </Button>

                        <Button
                            variant="outline"
                            onClick={() => handleExport(columnVisibility)}
                            className="cursor-pointer text-xs"
                        >
                            <FileSpreadsheet className="h-4 w-4" />

                            <span className="hidden sm:block">Export</span>
                        </Button>

                        <Button
                            className="cursor-pointer text-xs"
                            onClick={openCreate}
                        >
                            <Cog className="h-4 w-4" />

                            <span className="hidden sm:block">Tambah</span>
                        </Button>
                    </div>
                </div>

                {/* FILTER */}
                <div className="grid grid-cols-1 gap-3 rounded-xl border p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {/* SEARCH */}
                    <div className="space-y-1">
                        <Label className="text-[11px]">Nama / Username</Label>

                        <Input
                            placeholder="Cari nama atau username..."
                            value={localFilters.search}
                            onChange={(e) =>
                                handleFilterChange(
                                    localFilters,
                                    setLocalFilters,
                                    'search',
                                    e.target.value,
                                )
                            }
                            className="h-9 placeholder:text-xs"
                        />
                    </div>

                    {/* STORE */}
                    <div className="space-y-1">
                        <Label className="text-[11px]">Store</Label>

                        <select
                            value={localFilters.store_id ?? ''}
                            onChange={(e) => {
                                const value = e.target.value;

                                handleFilterChange(
                                    localFilters,
                                    setLocalFilters,
                                    'store_id',
                                    value ? Number(value) : undefined,
                                );
                            }}
                            className="h-9 w-full rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="">Semua Store</option>

                            {stores.map((store) => (
                                <option key={store.id} value={store.id}>
                                    {store.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* DIALOG FORM */}
                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogContent className="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>
                                {selectedUser ? 'Edit User' : 'Tambah User'}
                            </DialogTitle>

                            <DialogDescription>
                                {selectedUser
                                    ? 'Perbarui data user yang dipilih dan simpan perubahan.'
                                    : 'Tambahkan user baru ke dalam sistem.'}
                            </DialogDescription>
                        </DialogHeader>

                        <Form
                            close={() => {
                                setOpen(false);
                                setSelectedUser(null);
                            }}
                            stores={stores}
                            initialData={
                                selectedUser
                                    ? {
                                          id: selectedUser.id,
                                          name: selectedUser.name,
                                          username: selectedUser.username ?? '',
                                          email: selectedUser.email,
                                          store_id:
                                              selectedUser.store_id ??
                                              undefined,
                                      }
                                    : undefined
                            }
                        />
                    </DialogContent>
                </Dialog>

                {/* TABLE */}
                <DataTable<User>
                    columns={columnUsers(openEdit)}
                    data={users.data}
                    meta={users.meta}
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
