import { Link } from '@inertiajs/react';
import {
    create as createTrip,
    destroy as destroyTrip,
    edit as editTrip,
    index as tripIndex,
} from '@/actions/App/Http/Controllers/TripController';
import { confirmDelete } from '@/components/confirm-delete';
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
import { formatDate, formatNumber } from '@/lib/format';
import type { Option, Paginated, Trip } from '@/types/domain';

export default function TripIndex({
    trips,
    filters,
    vehicles,
    clients,
}: {
    trips: Paginated<Trip>;
    filters: Record<string, string>;
    vehicles: Option[];
    clients: Option[];
}) {
    return (
        <PageShell
            title="Trip"
            description="Daftar perjalanan truk"
            actions={
                <Button asChild>
                    <Link href={createTrip.url()}>Tambah Trip</Link>
                </Button>
            }
        >
            <FilterBar
                path={tripIndex.url()}
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
                        <Th>Tanggal</Th>
                        <Th>Plat</Th>
                        <Th>Sopir</Th>
                        <Th>Rute (client asal → tujuan)</Th>
                        <Th>No. Surat Jalan</Th>
                        <Th right>Total Qty</Th>
                        <Th>Status</Th>
                        <Th />
                    </tr>
                </thead>
                <tbody>
                    {trips.data.length === 0 && <EmptyRow colSpan={8} />}
                    {trips.data.map((trip) => (
                        <tr key={trip.id}>
                            <Td>{formatDate(trip.tanggal)}</Td>
                            <Td>{trip.vehicle?.plat}</Td>
                            <Td>{trip.vehicle?.nama_supir ?? trip.sopir}</Td>
                            <Td>
                                {trip.client?.nama} →{' '}
                                {trip.destination_client?.nama ?? '-'}
                            </Td>
                            <Td>{trip.no_surat_jalan}</Td>
                            <Td right>{formatNumber(trip.total_qty)}</Td>
                            <Td>
                                {trip.invoice_id ? (
                                    <Badge variant="secondary">
                                        Ter-invoice
                                    </Badge>
                                ) : (
                                    <Badge variant="outline">Belum</Badge>
                                )}
                            </Td>
                            <Td className="space-x-2 text-right whitespace-nowrap">
                                {!trip.invoice_id && (
                                    <>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link href={editTrip.url(trip.id)}>
                                                Ubah
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() =>
                                                confirmDelete(
                                                    destroyTrip.url(trip.id),
                                                    'Hapus trip ini?',
                                                )
                                            }
                                        >
                                            Hapus
                                        </Button>
                                    </>
                                )}
                            </Td>
                        </tr>
                    ))}
                </tbody>
            </DataTable>
            <Pagination paginator={trips} />
        </PageShell>
    );
}

TripIndex.layout = { breadcrumbs: [{ title: 'Trip', href: '/trips' }] };
