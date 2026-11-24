import { Link } from '@inertiajs/react';
import { useState } from 'react';
import tripRoutes from '@/routes/trips';
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
import { Input } from '@/components/ui/input';
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
                <>
                    <Button asChild>
                        <Link href={tripRoutes.create.url()}>Tambah Trip</Link>
                    </Button>
                    <a href={tripRoutes.template.url()} download className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2">
                        Unduh Template
                    </a>
                    <Button asChild variant="outline">
                        <Link href={tripRoutes.import.url()}>
                            Import Trip
                        </Link>
                    </Button>
                </>
            }
        >
            <FilterBar
                path={tripRoutes.index.url()}
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
                        <Th>Rute</Th>
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
                                {trip.category === "outsourcing"
                                    ? `${trip.client?.nama} → ${trip.tujuan ?? '-'}`
                                    : `${trip.client?.nama} → ${trip.destination_client?.nama ?? '-'}`}
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
                                            <Link href={tripRoutes.edit.url(trip.id)}>
                                                Ubah
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() =>
                                                confirmDelete(
                                                    tripRoutes.destroy.url(trip.id),
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

TripIndex.layout = { breadcrumbs: [{ title: 'Trip', href: tripRoutes.index.url() }] };
