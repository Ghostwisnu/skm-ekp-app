import { Link } from '@inertiajs/react';
import { FilterBar } from '@/components/filter-bar';
import {
    DataTable,
    EmptyRow,
    PageShell,
    Td,
    Th,
} from '@/components/page-shell';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate, formatRupiah } from '@/lib/format';
import type { Invoice, Option, Paginated } from '@/types/domain';

export default function InvoiceIndex({
    invoices,
    clients,
    filters,
}: {
    invoices: Paginated<Invoice>;
    clients: Option[];
    filters: Record<string, string>;
}) {
    return (
        <PageShell
            title="Invoice"
            description="Invoice per pasangan client asal dan tujuan"
            actions={
                <Button asChild>
                    <Link href="invoices/create">Buat Invoice</Link>
                </Button>
            }
        >
            <FilterBar
                path="/invoices"
                initial={filters}
                fields={[
                    {
                        name: 'client_id',
                        label: 'Client',
                        type: 'select',
                        options: clients,
                    },
                ]}
            />
            <DataTable>
                <thead>
                    <tr>
                        <Th>No. Invoice</Th>
                        <Th>Client asal → tujuan</Th>
                        <Th>Periode</Th>
                        <Th right>Subtotal</Th>
                        <Th right>PPN</Th>
                        <Th right>Total</Th>
                        <Th>Status</Th>
                    </tr>
                </thead>
                <tbody>
                    {invoices.data.length === 0 && <EmptyRow colSpan={7} />}
                    {invoices.data.map((invoice) => (
                        <tr key={invoice.id}>
                            <Td>
                                <Link
                                    href={`/invoices/${invoice.id}`}
                                    className="text-primary underline"
                                >
                                    {invoice.no_invoice}
                                </Link>
                            </Td>
                            <Td>
                                {invoice.client?.nama} → {invoice.destination_client?.nama ?? '-'}
                            </Td>
                            <Td>
                                {formatDate(invoice.tanggal_awal)} –{' '}
                                {formatDate(invoice.tanggal_akhir)}
                            </Td>
                            <Td right>{formatRupiah(invoice.subtotal)}</Td>
                            <Td right>{invoice.ppn_persen}%</Td>
                            <Td right>{formatRupiah(invoice.total)}</Td>
                            <Td>
                                <Badge
                                    variant={
                                        invoice.status === 'draft'
                                            ? 'outline'
                                            : 'secondary'
                                    }
                                    className={
                                        invoice.status === 'draft'
                                            ? 'border-amber-500 text-amber-600'
                                            : ''
                                    }
                                >
                                    {invoice.status === 'draft'
                                        ? 'Draft'
                                        : 'Final'}
                                </Badge>
                            </Td>
                        </tr>
                    ))}
                </tbody>
            </DataTable>
            <Pagination paginator={invoices} />
        </PageShell>
    );
}

InvoiceIndex.layout = {
    breadcrumbs: [{ title: 'Invoice', href: '/invoices' }],
};
