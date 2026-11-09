import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function PageShell({
    title,
    description,
    actions,
    children,
}: {
    title: string;
    description?: string;
    actions?: ReactNode;
    children: ReactNode;
}) {
    return (
        <>
            <Head title={title} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                    {actions && (
                        <div className="flex flex-wrap gap-2">{actions}</div>
                    )}
                </div>
                {children}
            </div>
        </>
    );
}

export function DataTable({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className="overflow-x-auto rounded-md border bg-card">
            <table className={cn('w-full text-sm', className)}>
                {children}
            </table>
        </div>
    );
}

export function Th({
    children,
    right,
}: {
    children?: ReactNode;
    right?: boolean;
}) {
    return (
        <th
            className={cn(
                'border-b bg-muted/50 px-3 py-2 text-xs font-medium whitespace-nowrap text-muted-foreground',
                right ? 'text-right' : 'text-left',
            )}
        >
            {children}
        </th>
    );
}

export function Td({
    children,
    right,
    colSpan,
    className,
}: {
    children?: ReactNode;
    right?: boolean;
    colSpan?: number;
    className?: string;
}) {
    return (
        <td
            colSpan={colSpan}
            className={cn(
                'border-b px-3 py-2 align-middle',
                right && 'text-right tabular-nums',
                className,
            )}
        >
            {children}
        </td>
    );
}

export function EmptyRow({ colSpan }: { colSpan: number }) {
    return (
        <tr>
            <Td
                colSpan={colSpan}
                className="py-8 text-center text-muted-foreground"
            >
                Belum ada data.
            </Td>
        </tr>
    );
}

export function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-1.5">
            <label className="text-sm font-medium">{label}</label>
            {children}
            {error && (
                <p className="text-sm text-red-600 dark:text-red-400">
                    {error}
                </p>
            )}
        </div>
    );
}
