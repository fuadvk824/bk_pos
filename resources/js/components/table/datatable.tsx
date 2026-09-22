import {
    flexRender,
    getCoreRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';
import type {
    ColumnDef,
    SortingState,
    VisibilityState,
} from '@tanstack/react-table';

import { useRef, useState } from 'react';

import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import type { PaginationMeta } from '@/types/custom/pagination';
import { Button } from '../ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '../ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '../ui/select';
import { LaravelPagination } from './pagination';
import { ArrowLeft, ArrowRight } from 'lucide-react';

interface DataTableProps<TData> {
    columns: ColumnDef<TData>[];
    data: TData[];
    meta: PaginationMeta;

    columnVisibility: VisibilityState;
    onColumnVisibilityChange?: (value: VisibilityState) => void;

    perPage: number;
    onPerPageChange: (value: number) => void;
}

export function DataTable<TData>({
    columns,
    data,
    meta,
    columnVisibility,
    onColumnVisibilityChange,
    perPage,
    onPerPageChange,
}: DataTableProps<TData>) {
    const [sorting, setSorting] = useState<SortingState>([]);
    const tableContainerRef = useRef<HTMLDivElement>(null);

    const scrollTable = (direction: 'left' | 'right') => {
        const container = tableContainerRef.current;
        if (!container) return;
        const amount = container.clientWidth * 0.7;

        container.scrollTo({
            left:
                direction === 'right'
                    ? container.scrollLeft + amount
                    : container.scrollLeft - amount,
            behavior: 'smooth',
        });
    };

    const table = useReactTable({
        data,
        columns,
        state: {
            columnVisibility: columnVisibility ?? {},
            sorting,
        },
        onSortingChange: setSorting,
        getSortedRowModel: getSortedRowModel(),
        getCoreRowModel: getCoreRowModel(),
        onColumnVisibilityChange: (updater) => {
            if (!onColumnVisibilityChange) return;

            if (typeof updater === 'function') {
                onColumnVisibilityChange(updater(columnVisibility));
            } else {
                onColumnVisibilityChange(updater);
            }
        },
    });

    return (
        <div className="space-y-4 pb-16 text-xs">
            <div className="flex justify-end gap-3">
                <div className="flex justify-end gap-1">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="h-8 w-8 p-0 text-lg cursor-pointer"
                        onClick={() => scrollTable('left')}
                    >
                        <ArrowLeft />
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="h-8 w-8 p-0 text-lg cursor-pointer"
                        onClick={() => scrollTable('right')}
                    >
                        <ArrowRight />
                    </Button>
                </div>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="outline"
                            size="sm"
                            className="cursor-pointer text-xs"
                        >
                            Show Columns
                        </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="start" className="w-56 text-xs">
                        {table.getAllLeafColumns().map((column) => {
                            const label =
                                typeof column.columnDef.header === 'string'
                                    ? column.columnDef.header
                                    : column.id;

                            return (
                                <DropdownMenuCheckboxItem
                                    key={column.id}
                                    checked={column.getIsVisible()}
                                    onCheckedChange={(value) =>
                                        column.toggleVisibility(!!value)
                                    }
                                    className="text-xs"
                                >
                                    {label}
                                </DropdownMenuCheckboxItem>
                            );
                        })}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <div
                ref={tableContainerRef}
                className="w-full overflow-x-auto rounded-md border"
            >
                <div className="w-max min-w-full">
                    <Table>
                        <TableHeader>
                            {table.getHeaderGroups().map((hg) => (
                                <TableRow key={hg.id}>
                                    {hg.headers.map((h) => (
                                        <TableHead
                                            key={h.id}
                                            className="bg-bk-dev cursor-pointer p-3 text-xs select-none"
                                            onClick={h.column.getToggleSortingHandler()}
                                        >
                                            <div className="flex items-center gap-2">
                                                {flexRender(
                                                    h.column.columnDef.header,
                                                    h.getContext(),
                                                )}
                                                {{
                                                    asc: ' ▲',
                                                    desc: ' ▼',
                                                }[
                                                    h.column.getIsSorted() as string
                                                ] ?? null}
                                            </div>
                                        </TableHead>
                                    ))}
                                </TableRow>
                            ))}
                        </TableHeader>

                        <TableBody>
                            {table.getRowModel().rows.length ? (
                                table.getRowModel().rows.map((row) => (
                                    <TableRow key={row.id}>
                                        {row.getVisibleCells().map((cell) => (
                                            <TableCell
                                                key={cell.id}
                                                className="p-3 text-xs"
                                            >
                                                {flexRender(
                                                    cell.column.columnDef.cell,
                                                    cell.getContext(),
                                                )}
                                            </TableCell>
                                        ))}
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={columns.length}
                                        className="p-3 text-center text-xs"
                                    >
                                        No data
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <div className="flex flex-col gap-4 text-xs md:flex-row md:items-center md:justify-between">
                <div className="flex items-center justify-between text-muted-foreground">
                    {meta.total && (
                        <span>
                            Showing {data.length} of {meta.total} results
                        </span>
                    )}
                    <div className="block sm:hidden">
                        <Select
                            value={String(perPage)}
                            onValueChange={(value) =>
                                onPerPageChange?.(Number(value))
                            }
                        >
                            <SelectTrigger className="w-28 text-xs">
                                <SelectValue>
                                    <span className="inline-flex items-center gap-1">
                                        {perPage}
                                        <span className="text-muted-foreground">
                                            rows
                                        </span>
                                    </span>
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent className="text-xs">
                                {[5, 10, 25, 50].map((size) => (
                                    <SelectItem
                                        key={size}
                                        value={String(size)}
                                        className="text-xs"
                                    >
                                        {size} rows
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="block h-px w-full bg-gray-200 sm:hidden" />

                <div className="flex items-center justify-center sm:justify-baseline">
                    <div className="hidden sm:block">
                        <Select
                            value={String(perPage)}
                            onValueChange={(value) =>
                                onPerPageChange?.(Number(value))
                            }
                        >
                            <SelectTrigger className="w-28 text-xs">
                                <SelectValue>
                                    <span className="inline-flex items-center gap-1">
                                        {perPage}
                                        <span className="text-muted-foreground">
                                            rows
                                        </span>
                                    </span>
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent
                                position="popper"
                                side="top"
                                sideOffset={4}
                                className="text-xs"
                            >
                                {[5, 10, 25, 50, 100, 500].map((size) => (
                                    <SelectItem
                                        key={size}
                                        value={String(size)}
                                        className="text-xs"
                                    >
                                        {size} rows
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="text-xs">
                        <LaravelPagination meta={meta} />
                    </div>
                </div>
            </div>
        </div>
    );
}
