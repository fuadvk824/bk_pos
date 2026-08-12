import { usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';

export function useRoute() {
    const { ziggy } = usePage().props as any;
    return (name: string, params = {}) => route(name, params, false, ziggy);
}
