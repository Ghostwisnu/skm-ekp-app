import { FilterBar } from '@/components/filter-bar';
import {
    DataTable,
    EmptyRow,
    PageShell,
    Td,
    Th,
} from '@/components/page-shell';
import { Button } from '@/components/ui/button';
import { cleanQuery, formatNumber, formatRupiah } from '@/lib/format';
import type { Option, VehicleReportRow } from '@/types/domain';

export default function VehicleReport({
    rows,
    filters,
    vehicles,
}: {
    rows: VehicleReportRow[];
    filters: Record<string, string>;
    vehicles: Option[];
}) {
    const query = new URLSearchParams(cleanQuery(filters)).toString();
    const suffix = query ? `?${query}` : '';

    return (
        <PageShell
            title="Laporan Kinerja Plat"
            description="Trip, jumlah barang, nilai invoice (sebelum PPN), uang jalan, dan selisih per plat"
            actions={
                <>
                    <Button variant="outline" asChild>
                        <a href={`/reports/vehicles/pdf${suffix}`}>Cetak PDF</a>
                    </Button>
                    <Button variant="outline" asChild>
                        <a href={`/reports/vehicles/excel${suffix}`}>
                            Ekspor Excel
                        </a>
                    </Button>
                </>
            }
        >
            <FilterBar
                path="/reports/vehicles"
                initial={filters}
                fields={[
                    { name: 'from', label: 'Dari tanggal', type: 'date' },
                    { name: 'to', label: 'Sampai tanggal', type: 'date' },
                    {
                        name: 'vehicle_id',
                        label: 'Plat',
                        type: 'select',
                        options: vehicles,
                    },
                ]}
            />
            <DataTable>
                <thead>
                    <tr>
                        <Th>Plat</Th>
                        <Th right>Trip</Th>
                        <Th right>Qty Barang</Th>
                        <Th right>Nilai Invoice</Th>
                        <Th right>Biaya</Th>
                        <Th right>Selisih</Th>
                    </tr>
                </thead>
                <tbody>
                    {rows.length === 0 && <EmptyRow colSpan={6} />}
                    {rows.map((row) => (
                        <tr key={row.vehicle_id}>
                            <Td>{row.plat}</Td>
                            <Td right>{row.trip}</Td>
                            <Td right>{formatNumber(row.jumlah_barang)}</Td>
                            <Td right>{formatRupiah(row.nilai_invoice)}</Td>
                            <Td right>{formatRupiah(row.biaya)}</Td>
                            <Td right className="font-semibold text-amber-600">
                                {formatRupiah(row.margin)}
                            </Td>
                        </tr>
                    ))}
                </tbody>
            </DataTable>
        </PageShell>
    );
}

VehicleReport.layout = {
    breadcrumbs: [{ title: 'Laporan Kinerja Plat', href: '/reports/vehicles' }],
};
