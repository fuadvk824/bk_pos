import { router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';

import { Eye, Wallet, ArrowRight, Trash2 } from 'lucide-react';

import { useRoute } from '@/lib/route-ziggy';
import { Transaction } from '@/types/custom/transaction';
import { formatRupiah } from '@/lib/format-rupiah';

export const columnTransactions = (
    onProcess?: (transaction: Transaction) => void,
    onMutation?: (transaction: Transaction) => void,
    mutationLoading?: boolean,
): ColumnDef<Transaction>[] => [
    {
        accessorKey: 'invoice_number',
        header: 'Invoice',
    },

    {
        accessorKey: 'customer_name',
        header: 'Customer',
    },

    {
        accessorKey: 'customer_address',
        header: 'Alamat',
    },

    {
        accessorKey: 'store_name',
        header: 'Store',
    },

    {
        accessorKey: 'total',
        header: 'Total',
        cell: ({ row }) => formatRupiah(Number(row.getValue('total'))),
    },

    {
        accessorKey: 'payment_status',
        header: 'Status',
    },

    {
        accessorKey: 'created_at',
        header: 'Tanggal',
    },

    {
        id: 'actions',
        header: 'Action',

        cell: ({ row }) => {
            const route = useRoute();
            const transaction = row.original;

            const handleDestroy = () => {
                const confirmed = window.confirm(
                    `Yakin ingin menghapus transaksi ${transaction.invoice_number}?\n\n` +
                        `Stok produk dan point customer akan dikembalikan, ` +
                        `kemudian transaksi akan dihapus secara permanen.`,
                );

                if (!confirmed) {
                    return;
                }

                router.delete(route('transaction.destroy', transaction.id), {
                    preserveScroll: true,
                });
            };

            return (
                <div className="flex gap-2">
                    {/* DETAIL */}
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() =>
                            router.get(
                                route('transaction.show', transaction.id),
                            )
                        }
                        className="cursor-pointer"
                    >
                        <Eye className="h-4 w-4" />
                        Detail
                    </Button>

                    {/* PROCESS */}
                    <Button
                        size="sm"
                        onClick={() => onProcess?.(transaction)}
                        disabled={
                            transaction.payment_status === 'paid' ||
                            mutationLoading
                        }
                        className="cursor-pointer"
                    >
                        <Wallet className="h-4 w-4" />

                        {transaction.payment_status === 'paid'
                            ? 'Lunas'
                            : 'Proses'}
                    </Button>

                    {/* MUTATION */}
                    {transaction.is_backorder && (
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => onMutation?.(transaction)}
                            // disabled={mutationLoading}
                            disabled
                            className="cursor-pointer"
                        >
                            <ArrowRight className="h-4 w-4" />
                            Mutasi
                        </Button>
                    )}

                    {/* DELETE */}
                    <Button
                        size="sm"
                        variant="destructive"
                        onClick={handleDestroy}
                        className="cursor-pointer"
                    >
                        <Trash2 className="h-4 w-4" />
                        Hapus
                    </Button>
                </div>
            );
        },
    },
];

// export const columnTransactions = (
//     onProcess?: (transaction: Transaction) => void,
//     onMutation?: (transaction: Transaction) => void,
//     mutationLoading?: boolean,
// ): ColumnDef<Transaction>[] => [
//     {
//         accessorKey: 'invoice_number',
//         header: 'Invoice',
//     },
//     {
//         accessorKey: 'customer_name',
//         header: 'Customer',
//     },
//     {
//         accessorKey: 'customer_address',
//         header: 'Alamat',
//     },
//     {
//         accessorKey: 'store_name',
//         header: 'Store',
//     },
//     {
//         accessorKey: 'total',
//         header: 'Total',
//         cell: ({ row }) =>
//             formatRupiah(
//                 Number(row.getValue('total')),
//             ),
//     },
//     {
//         accessorKey: 'payment_status',
//         header: 'Status',
//     },
//     {
//         accessorKey: 'created_at',
//         header: 'Tanggal',
//     },
//     {
//         id: 'actions',
//         header: 'Action',

//         cell: ({ row }) => {
//             const route = useRoute();

//             const transaction =
//                 row.original;

//             return (
//                 <div className="flex gap-2">

//                     <Button
//                         size="sm"
//                         variant="outline"
//                         onClick={() =>
//                             router.get(
//                                 route(
//                                     'transaction.show',
//                                     transaction.id,
//                                 ),
//                             )
//                         }
//                         className="cursor-pointer"
//                     >
//                         <Eye className="h-4 w-4" />
//                         Detail
//                     </Button>

//                     <Button
//                         size="sm"
//                         onClick={() =>
//                             onProcess?.(
//                                 transaction,
//                             )
//                         }
//                         disabled={
//                             transaction.payment_status ===
//                                 'paid' ||
//                             mutationLoading
//                         }
//                         className="cursor-pointer"
//                     >
//                         <Wallet className="h-4 w-4" />

//                         {transaction.payment_status ===
//                         'paid'
//                             ? 'Lunas'
//                             : 'Proses'}
//                     </Button>

//                     {transaction.is_backorder && (
//                         <Button
//                             size="sm"
//                             variant="secondary"
//                             onClick={() =>
//                                 onMutation?.(
//                                     transaction,
//                                 )
//                             }
//                             disabled={
//                                 mutationLoading
//                             }
//                             className="cursor-pointer"
//                         >
//                             <ArrowRight className="h-4 w-4" />

//                             Mutasi
//                         </Button>
//                     )}
//                 </div>
//             );
//         },
//     },
// ];
