import { router } from '@inertiajs/react';
import { confirmDelete } from '@/components/confirm-delete';
import { DataTable, PageShell, Td, Th } from '@/components/page-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, formatNumber, formatRupiah } from '@/lib/format';
import type { Invoice } from '@/types/domain';

type CompanyDetails = {
    name: string;
    address: string;
    bank_name: string;
    bank_account: string;
};

export default function InvoiceShow({
    invoice,
    company,
}: {
    invoice: Invoice;
    company: CompanyDetails;
}) {
    const isDraft = invoice.status === 'draft';

    return (
        <PageShell
            title={`Invoice ${invoice.no_invoice}`}
            description={`${invoice.client?.nama} → ${invoice.destination_client?.nama ?? '-'} • Tanggal dokumen: ${formatDate(invoice.tanggal_akhir)} • Periode: ${formatDate(invoice.tanggal_awal)} – ${formatDate(invoice.tanggal_akhir)}`}
            actions={
                <>
                    <Button variant="outline" asChild>
                        <a href={`/invoices/${invoice.id}/pdf`}>Cetak PDF</a>
                    </Button>
                    <Button variant="outline" asChild>
                        <a href={`/invoices/${invoice.id}/excel`}>
                            Ekspor Excel
                        </a>
                    </Button>
                    {isDraft && (
                        <>
                            <Button
                                onClick={() =>
                                    router.post(
                                        `/invoices/${invoice.id}/finalize`,
                                    )
                                }
                            >
                                Finalkan
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={() =>
                                    confirmDelete(
                                        `/invoices/${invoice.id}`,
                                        'Hapus invoice draft ini?',
                                    )
                                }
                            >
                                Hapus
                            </Button>
                        </>
                    )}
                </>
            }
        >
            <section className="grid gap-4 rounded-md border bg-card p-4 sm:grid-cols-2">
                <div>
                    <h2 className="font-semibold">
                        {company.name || 'Perusahaan pemilik'}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {company.address}
                    </p>
                </div>
                <div className="text-sm">
                    <p className="font-medium">Pembayaran</p>
                    <p>{company.bank_name}</p>
                    <p>No. Rekening: {company.bank_account}</p>
                </div>
            </section>
            <div className="grid gap-4 lg:grid-cols-[1fr_20rem]">
                <DataTable>
                    <thead>
                        <tr>
                            <Th>No. Surat Jalan</Th>
                            <Th>Tanggal</Th>
                            <Th>Plat</Th>
                            <Th>Barang</Th>
                            <Th>Rute</Th>
                            <Th right>Qty</Th>
                            <Th right>Harga satuan / flat</Th>
                            <Th right>Jumlah</Th>
                        </tr>
                    </thead>
                    <tbody>
                        {invoice.lines?.map((line) => (
                            <tr key={line.id}>
                                <Td>{line.no_surat_jalan}</Td>
                                <Td>{formatDate(line.tanggal)}</Td>
                                <Td>{line.plat}</Td>
                                <Td>{line.jenis_barang}</Td>
                                <Td>{line.rute ?? '-'}</Td>
                                <Td right>
                                    {formatNumber(
                                        line.qty_barang ?? line.tonase_kg,
                                    )}
                                </Td>
                                <Td right>
                                    {formatRupiah(
                                        line.harga_satuan ?? line.harga_per_kg,
                                    )}
                                    {line.pricing_mode === 'flat_trip' &&
                                        ' (flat)'}
                                </Td>
                                <Td right>{formatRupiah(line.jumlah)}</Td>
                            </tr>
                        ))}
                    </tbody>
                </DataTable>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            Ringkasan
                            <Badge variant={isDraft ? 'outline' : 'secondary'}>
                                {isDraft ? 'Draft' : 'Final'}
                            </Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid grid-cols-2 gap-1 text-sm tabular-nums">
                            <dt>Subtotal</dt>
                            <dd className="text-right">
                                {formatRupiah(invoice.subtotal)}
                            </dd>
                            <dt>PPN {invoice.ppn_persen}%</dt>
                            <dd className="text-right">
                                {formatRupiah(invoice.ppn_nominal)}
                            </dd>
                            <dt className="font-semibold">Total</dt>
                            <dd className="text-right font-semibold">
                                {formatRupiah(invoice.total)}
                            </dd>
                        </dl>
                    </CardContent>
                </Card>
            </div>
            <div className="ml-auto grid min-h-40 w-64 content-between text-center text-sm">
                <p>Hormat kami,</p>
                <p>
                    Atas nama,
                    <br />
                    <span className="font-semibold">
                        {company.name || 'Perusahaan pemilik'}
                    </span>
                </p>
            </div>
        </PageShell>
    );
}

InvoiceShow.layout = { breadcrumbs: [{ title: 'Invoice', href: '/invoices' }] };
