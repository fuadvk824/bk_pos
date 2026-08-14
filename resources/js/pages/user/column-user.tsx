import { router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal, SquarePen, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { route } from 'ziggy-js';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogMedia,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

import type { User } from '@/types/custom/user';

const handleToast = (page: any) => {
    const flash = page.props.flash as {
        success?: string;
        error?: string;
    };

    if (flash?.success) {
        toast.success(flash.success);
    }

    if (flash?.error) {
        toast.error(flash.error);
    }
};

const deleteUser = (id: number) => {
    router.delete(
        route('user.destroy', {
            user: id,
        }),
        {
            preserveScroll: true,
            onSuccess: handleToast,
        },
    );
};

export const columnUsers = (
    onEdit: (user: User) => void,
): ColumnDef<User>[] => [
    {
        id: 'select',

        header: ({ table }) => (
            <Checkbox
                checked={table.getIsAllPageRowsSelected()}
                onCheckedChange={(value) =>
                    table.toggleAllPageRowsSelected(!!value)
                }
                aria-label="Select all"
            />
        ),

        cell: ({ row }) => (
            <Checkbox
                checked={row.getIsSelected()}
                onCheckedChange={(value) => row.toggleSelected(!!value)}
                aria-label="Select row"
            />
        ),

        enableSorting: false,
        enableHiding: false,
    },

    {
        accessorKey: 'name',

        header: 'Nama Kasir',

        cell: ({ row }) => row.getValue('name') ?? '-',
    },

    {
        accessorKey: 'email',

        header: 'Email',

        cell: ({ row }) => row.getValue('email') ?? '-',
    },

    {
        accessorKey: 'username',

        header: 'Username',

        cell: ({ row }) => row.getValue('username') ?? '-',
    },

    {
        accessorKey: 'store',

        header: 'Store',

        cell: ({ row }) => row.getValue('store') ?? '-',
    },

    {
        id: 'actions',

        header: 'Action',

        enableHiding: false,

        cell: ({ row }) => {
            const user = row.original;

            const [openDropdown, setOpenDropdown] = useState(false);

            const [openDelete, setOpenDelete] = useState(false);

            const handleDelete = () => {
                deleteUser(user.id);

                setOpenDelete(false);
                setOpenDropdown(false);
            };

            return (
                <>
                    <DropdownMenu
                        open={openDropdown}
                        onOpenChange={setOpenDropdown}
                    >
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon">
                                <MoreHorizontal />
                            </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end">
                            {/* EDIT */}
                            <DropdownMenuItem
                                onClick={() => onEdit(user)}
                                className="cursor-pointer"
                            >
                                <SquarePen />
                                Edit
                            </DropdownMenuItem>

                            {/* DELETE */}
                            <AlertDialog
                                open={openDelete}
                                onOpenChange={setOpenDelete}
                            >
                                <AlertDialogTrigger asChild>
                                    <DropdownMenuItem
                                        className="cursor-pointer text-red-600"
                                        onSelect={(e) => e.preventDefault()}
                                    >
                                        <Trash2 className="text-red-600" />
                                        Delete
                                    </DropdownMenuItem>
                                </AlertDialogTrigger>

                                <AlertDialogContent size="sm">
                                    <AlertDialogHeader>
                                        <AlertDialogMedia>
                                            <Trash2 className="text-red-600" />
                                        </AlertDialogMedia>

                                        <AlertDialogTitle>
                                            Delete User
                                        </AlertDialogTitle>

                                        <AlertDialogDescription>
                                            Apakah kamu yakin ingin menghapus
                                            data{' '}
                                            <span className="font-semibold text-red-600">
                                                {user.name}
                                            </span>
                                            ?
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>

                                    <AlertDialogFooter>
                                        <AlertDialogCancel className="cursor-pointer">
                                            Batal
                                        </AlertDialogCancel>

                                        <AlertDialogAction
                                            className="cursor-pointer bg-red-600 hover:bg-red-700"
                                            onClick={handleDelete}
                                        >
                                            Delete
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </>
            );
        },
    },
];
