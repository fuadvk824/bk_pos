import type { ColumnDef } from '@tanstack/react-table';
import { Customer } from '@/types/custom/customer';

export const columnCustomers: ColumnDef<Customer>[] = [
    {
        accessorKey: 'name',
        header: 'Nama Customer',
    },
    {
        accessorKey: 'phone',
        header: 'No HP',
        cell: ({ row }) => row.original.phone ?? '-',
    },
    {
        accessorKey: 'address',
        header: 'Alamat',
        cell: ({ row }) => row.original.address ?? '-',
    },
    {
        accessorKey: 'current_point',
        header: 'Point',
          cell: ({ row }) => row.original.current_point ?? '0',
    },
  
];
