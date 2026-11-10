import { Link } from '@inertiajs/react';
import {
    DataTable,
    EmptyRow,
    PageShell,
    Td,
    Th,
} from '@/components/page-shell';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, formatNumber, formatRupiah } from '@/lib/format';
import { dashboard } from '@/routes';
import type { Trip } from '@/types/domain';

type Stats = {
    trips: number;
    jumlah_barang: number;
    biaya: number;
    invoice_count: number;
    invoice_total: number;
};

export default function Dashboard({
    periode,
    stats,
    recentTrips,
}: {
    periode: string;
    stats: Stats;
    recentTrips: Trip[];
}) {
    const cards = [
        ['Trip', formatNumber(stats.trips)],
        ['Qty Barang', formatNumber(stats.jumlah_barang)],
        ['Biaya Harian', formatRupiah(stats.biaya)],
        [
            'Invoice',
            `${stats.invoice_count} • ${formatRupiah(stats.invoice_total)}`,
        ],
    ];

    return (
        <PageShell title="Dashboard" description={`Ringkasan ${periode}`}>
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {cards.map(([label, value]) => (
                    <Card key={label}>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                {label}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold tabular-nums">
                            {value}
                        </CardContent>
                    </Card>
                ))}
            </div>

            <h2 className="text-base font-medium">Trip terbaru</h2>
            <DataTable>
                <thead>
                    <tr>
                        <Th>Tanggal</Th>
                        <Th>Plat</Th>
                        <Th>Client</Th>
                        <Th>No. Surat Jalan</Th>
                        <Th right>Total Qty</Th>
                    </tr>
                </thead>
                <tbody>
                    {recentTrips.length === 0 && <EmptyRow colSpan={5} />}
                    {recentTrips.map((trip) => (
                        <tr key={trip.id}>
                            <Td>{formatDate(trip.tanggal)}</Td>
                            <Td>{trip.vehicle?.plat}</Td>
                            <Td>{trip.client?.nama}</Td>
                            <Td>{trip.no_surat_jalan}</Td>
                            <Td right>{formatNumber(trip.total_qty)}</Td>
                        </tr>
                    ))}
                </tbody>
            </DataTable>
            <Link href="trips" className="text-sm text-primary underline">
                Lihat semua trip
            </Link>
        </PageShell>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
