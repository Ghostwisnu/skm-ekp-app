import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type ConfirmDeleteProps = {
    url: string;
    children: React.ReactNode;
    onDeleted?: () => void;
    message?: string;
};

export function ConfirmDelete({
    url,
    children,
    onDeleted,
    message = 'Hapus data ini? Tindakan tidak dapat dibatalkan.',
}: ConfirmDeleteProps) {
    const [open, setOpen] = useState(false);

    const handleConfirm = () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
        setOpen(false);
        onDeleted?.();
    };

    return (
        <>
            <div onClick={() => setOpen(true)} style={{ display: 'contents' }}>
                {children}
            </div>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Konfirmasi Hapus</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">{message}</p>
                    <div className="flex justify-end gap-2 mt-4">
                        <Button variant="outline" onClick={() => setOpen(false)}>Batal</Button>
                        <Button variant="destructive" onClick={handleConfirm}>Hapus</Button>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}