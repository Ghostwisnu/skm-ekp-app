import { router } from '@inertiajs/react';

export function confirmDelete(url: string, message = 'Hapus data ini?'): void {
    if (window.confirm(message)) {
        router.delete(url, { preserveScroll: true });
    }
}
