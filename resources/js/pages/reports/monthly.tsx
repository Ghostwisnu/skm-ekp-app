import { FilterBar } from '@/components/filter-bar';
import {
    DataTable,
    EmptyRow,
    PageShell,
    Td,
    Th,
} from '@/components/page-shell';
import { Button } from '@/components/ui/button';
import { formatNumber, formatRupiah } from '@/lib/format';
import type { MonthlyReportData, Option } from '@/types/domain';
import reports from '@/routes/reports';

export default function MonthlyReport({
    data,
    filters,
    vehicles,
}: {
    data: MonthlyReportData;
    filters: Record<string, string | undefined>;
    vehicles: Option[];
}) {
    const { days, summary, month, year } = data;

    return (
        <PageShell
            title="Laporan Bulanan"
            description={`Ringkasan harian Bulan ${month} Tahun ${year}`}
            actions={
                <></>
            }
        >
            <FilterBar
                path={reports.monthly.url()}
                initial={filters}
                fields={[
                    { name: 'month', label: 'Bulan', type: 'select', options: [
                        { id: '1', nama: 'Januari' }, { id: '2', nama: 'Februari' },
                        { id: '3', nama: 'Maret' }, { id: '4', nama: 'April' },
                        { id: '5', nama: 'Mei' }, { id: '6', nama: 'Juni' },
                        { id: '7', nama: 'Juli' }, { id: '8', nama: 'Agustus' },
                        { id: '9', nama: 'September' }, { id: '10', nama: 'Oktober' },
                        { id: '11', nama: 'November' }, { id: '12', nama: 'Desember' },
                    ]},
                    { name: 'year', label: 'Tahun', type: 'select', options: [
                        { id: '2024', nama: '2024' }, { id: '2025', nama: '2025' },
                        { id: '2026', nama: '2026' }, { id: '2027', nama: '2027' },
                    ]},
                    {
                        name: 'vehicle_id',
                        label: 'Plat',
                        type: 'select',
                        options: vehicles,
                    },
                ]}
            />
            <div className="overflow-x-auto">
                <DataTable>
                    <thead>
                        <tr>
                            <Th>Tanggal</Th>
                            <Th right>Trip</Th>
                            <Th right>Biaya Harian</Th>
                            <Th right>Invoice</Th>
                            <Th right>Profit / Day</Th>
                        </tr>
                    </thead>
                    <tbody>
                        {days.length === 0 && <EmptyRow colSpan={5} />}
                        {days.map((day, index) => (
                            <tr key={`${day.tanggal}-${index}`}>
                                <Td>{day.tanggal}</Td>
                                <Td right>{formatNumber(day.trip)}</Td>
                                <Td right>{formatRupiah(day.biaya_harian)}</Td>
                                <Td right>{formatRupiah(day.invoice)}</Td>
                                <Td right className={`font-semibold ${day.profit >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                    {formatRupiah(day.profit)}
                                </Td>
                            </tr>
                        ))}
                        <tr className="bg-muted/50 font-bold">
                            <Td>Total</Td>
                            <Td right>{formatNumber(summary.total_trip)}</Td>
                            <Td right>{formatRupiah(summary.total_biaya)}</Td>
                            <Td right>{formatRupiah(summary.total_invoice)}</Td>
                            <Td right className={summary.total_profit >= 0 ? 'text-green-600' : 'text-red-600'}>
                                {formatRupiah(summary.total_profit)}
                            </Td>
                        </tr>
                    </tbody>
                </DataTable>
            </div>
        </PageShell>
    );
}

MonthlyReport.layout = {
    breadcrumbs: [{ title: 'Laporan Bulanan', href: reports.monthly.url() }],
};