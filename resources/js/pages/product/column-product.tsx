import { router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal, Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Product } from '@/types/custom/product';
import { useRoute } from '@/lib/route-ziggy';

export const columnProducts = (): ColumnDef<Product>[] => [
    {
        accessorKey: 'product_code',
        header: 'Kode Produk',
    },
    {
        accessorKey: 'image',
        header: 'Image',
        cell: ({ row }) => {
            const image = row.getValue('image') as string;

            return image ? (
                <img
                    src={`/storage/${image}`}
                    alt="product"
                    className="h-10 w-10 rounded object-cover"
                />
            ) : (
                '-'
            );
        },
    },
    {
        accessorKey: 'name',
        header: 'Nama Produk',
    },
    {
        accessorKey: 'category',
        header: 'Kategori',
    },
    {
        accessorKey: 'stock_all',
        header: 'Stock',
    },
    {
        id: 'actions',
        header: 'Action',
        cell: ({ row }) => {
            const route = useRoute();
            const product = row.original;

            return (
                <Button
                    size="sm"
                    variant="outline"
                    onClick={() =>
                        router.get(route('product.show', product.id))
                    }
                    className='cursor-pointer hover:scale-105'
                >
                    <Eye className="h-4 w-4" /> Detail
                </Button>
            );
        },
    },
];
