import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/domain';

export function Pagination({ paginator }: { paginator: Paginated<unknown> }) {
    if (paginator.last_page <= 1) {
        return null;
    }

    return (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
            <span>
                Halaman {paginator.current_page} dari {paginator.last_page} (
                {paginator.total} data)
            </span>
            <div className="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    asChild
                    disabled={!paginator.prev_page_url}
                >
                    <Link href={paginator.prev_page_url ?? '#'} preserveScroll>
                        Sebelumnya
                    </Link>
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    asChild
                    disabled={!paginator.next_page_url}
                >
                    <Link href={paginator.next_page_url ?? '#'} preserveScroll>
                        Berikutnya
                    </Link>
                </Button>
            </div>
        </div>
    );
}
