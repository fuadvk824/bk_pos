import { router } from '@inertiajs/react';
import type { VisibilityState } from '@tanstack/react-table';
import { useRoute } from './route-ziggy';

interface UseTableActionsProps {
    filters: Record<string, any>;
    indexRoute: string;
    exportRoute: string;
    allColumns: string[];
}

export function useTableActions({
    filters,
    indexRoute,
    exportRoute,
    allColumns,
}: UseTableActionsProps) {
    const route = useRoute();
    const handleFilterChange = (
        localFilters: Record<string, any>,
        setLocalFilters: (val: any) => void,
        key: string,
        value: any,
    ) => {
        const updated = {
            ...localFilters,
            [key]: value === 'all' ? undefined : value,
            page: 1, // reset
        };

        setLocalFilters(updated);

        router.get(route(indexRoute), updated, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const handleExport = (columnVisibility: VisibilityState) => {
        const defaultVisibility = Object.fromEntries(
            allColumns.map((col) => [col, true]),
        );

        const mergedVisibility = {
            ...defaultVisibility,
            ...columnVisibility,
        };

        const visibleColumns = Object.entries(mergedVisibility)
            .filter(([, visible]) => visible)
            .map(([column]) => column);

        const params = new URLSearchParams();

        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                params.append(key, String(value));
            }
        });

        visibleColumns.forEach((col) => {
            params.append('columns[]', col);
        });

        window.location.href = route(exportRoute) + '?' + params.toString();
    };

    return {
        handleFilterChange,
        handleExport,
    };
}
