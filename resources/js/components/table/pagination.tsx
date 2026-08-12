import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import type { PaginationMeta } from '@/types/custom/pagination';

interface Props {
    meta: PaginationMeta;
    className?: string;
}

export function LaravelPagination({ meta, className }: Props) {
    const { current_page, last_page } = meta;

    if (last_page <= 1) return null;

    const pages: number[] = [];

    if (current_page <= 4) {
        pages.push(1, 2, 3, 4);
    } else if (current_page >= last_page - 3) {
        pages.push(last_page - 3, last_page - 2, last_page - 1, last_page);
    } else {
        pages.push(current_page, current_page + 1);
    }

    const uniquePages = [...new Set(pages)].filter((p) => p >= 1 && p <= last_page);

    const linkMap = new Map(meta.links.filter((l) => !isNaN(Number(l.label))).map((l) => [Number(l.label), l]));

    const prev = meta.links[0];
    const next = meta.links[meta.links.length - 1];

    return (
        <div className={`ms-4 flex items-center gap-1 ${className ?? ''}`}>
            <Button size="sm" variant="outline" disabled={!prev.url} asChild>
                <Link
                    href={prev.url ?? '#'}
                    preserveScroll
                    dangerouslySetInnerHTML={{ __html: prev.label }}
                    className="text-xs"
                />
            </Button>

            {uniquePages[0] !== 1 && (
                <>
                    <Button size="sm" variant="outline" asChild>
                        <Link href={linkMap.get(1)!.url!} className="text-xs">
                            1
                        </Link>
                    </Button>
                    <span className="px-1">...</span>
                </>
            )}

            {uniquePages.map((page) => {
                const link = linkMap.get(page);
                if (!link) return null;

                return (
                    <Button key={page} size="sm" variant={link.active ? 'default' : 'outline'} asChild>
                        <Link href={link.url!} preserveScroll className="text-xs">
                            {page}
                        </Link>
                    </Button>
                );
            })}

            {uniquePages.at(-1)! !== last_page && (
                <>
                    <span className="px-1">...</span>
                    <Button size="sm" variant="outline" asChild>
                        <Link href={linkMap.get(last_page)!.url!} className="text-xs">
                            {last_page}
                        </Link>
                    </Button>
                </>
            )}

            <Button size="sm" variant="outline" disabled={!next.url} asChild>
                <Link
                    href={next.url ?? '#'}
                    preserveScroll
                    dangerouslySetInnerHTML={{ __html: next.label }}
                    className="text-xs"
                />
            </Button>
        </div>
    );
}
