import { Link, router } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
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
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, formatNumber } from '@/lib/format';
import type { Option, Paginated, Trip } from '@/types/domain';

type TripPreview = {
    rows: Array<{
        tanggal: string;
        plat: string;
        no_surat_jalan: string;
        category: string;
        client_name: string;
        destination_client: string;
        tujuan: string;
        jenis_barang: string;
        qty_barang: number;
        total_harga: number;
    }>;
    groups: Array<{
        no_surat_jalan: string;
        tanggal: string;
        plat: string;
        client_name: string;
        destination_client?: string;
        category: string;
        tujuan: string;
        items: Array<{ jenis_barang: string; qty_barang: number; total_harga: number }>;
    }>;
    totalTrips: number;
    totalItems: number;
    totalQty: number;
    totalHarga: number;
    errors: string[];
    clients: string[];
    destinations: string[];
};

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
    const [isImportDialogOpen, setIsImportDialogOpen] = useState(false);
    const [isPreviewDialogOpen, setIsPreviewDialogOpen] = useState(false);
    const [previewData, setPreviewData] = useState<TripPreview | null>(null);
    const importForm = useForm<{ file: File | null }>({
        file: null,
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] ?? null;
        importForm.setData('file', file);

        if (file) {
            const formData = new FormData();
            formData.append('file', file);

            router.post(
                tripRoutes.preview.url(),
                formData,
                {
                    preserveScroll: true,
                    onSuccess: (response) => {
                        const data = response.props.preview ?? response.data;
                        setPreviewData(data);
                        setIsPreviewDialogOpen(true);
                    },
                    onError: (err) => {
                        console.error('Preview error:', err);
                    },
                }
            );
        }
    };

    const handleImportSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        if (importForm.data.file) {
            const formData = new FormData();
            formData.append('file', importForm.data.file);
            router.post(tripRoutes.import.url(), formData, {
                preserveScroll: true,
                onSuccess: () => {
                    setIsImportDialogOpen(false);
                    setIsPreviewDialogOpen(false);
                    importForm.reset();
                    setPreviewData(null);
                },
            });
        }
    };

    const handleConfirmImport = () => {
        if (importForm.data.file && previewData) {
            const formData = new FormData();
            formData.append('file', importForm.data.file);
            router.post(tripRoutes.import.url(), formData, {
                preserveScroll: true,
                onSuccess: () => {
                    setIsImportDialogOpen(false);
                    setIsPreviewDialogOpen(false);
                    importForm.reset();
                    setPreviewData(null);
                },
            });
        }
    };

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
                    <Button onClick={() => setIsImportDialogOpen(true)} variant="outline">
                        Import Trip
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

            {/* Import Dialog */}
            <Dialog open={isImportDialogOpen} onOpenChange={setIsImportDialogOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Import Trip</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleImportSubmit} className="space-y-4">
                        <div>
                            <Label>File Excel</Label>
                            <Input
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                onChange={handleFileChange}
                                className="block w-full"
                                required
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsImportDialogOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={importForm.processing || !previewData}
                            >
                                Import
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Preview Dialog */}
            <Dialog open={isPreviewDialogOpen} onOpenChange={setIsPreviewDialogOpen}>
                <DialogContent className="max-w-4xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Preview Data Import Trip</DialogTitle>
                    </DialogHeader>

                    {previewData && (
                        <div className="space-y-4">
                            {/* Summary */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Ringkasan</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-4 gap-4">
                                        <div>
                                            <p className="text-sm text-muted-foreground">Total Trip</p>
                                            <p className="text-xl font-bold">{previewData.totalTrips}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">Total Item</p>
                                            <p className="text-xl font-bold">{previewData.totalItems}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">Total Qty</p>
                                            <p className="text-xl font-bold">{formatNumber(previewData.totalQty)}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">Total Harga</p>
                                            <p className="text-xl font-bold">Rp {formatNumber(previewData.totalHarga)}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Errors */}
                            {previewData.errors.length > 0 && (
                                <Card className="border-destructive">
                                    <CardHeader>
                                        <CardTitle className="text-destructive">Kesalahan Ditemukan</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ul className="list-disc list-inside text-sm">
                                            {previewData.errors.map((error, index) => (
                                                <li key={index}>{error}</li>
                                            ))}
                                        </ul>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Trips Preview */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Data yang Akan Diimpor ({previewData.groups.length} Trip)</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {previewData.groups.map((group, index) => (
                                        <div key={index} className="mb-4 p-4 border rounded">
                                            <div className="flex justify-between items-center mb-2">
                                                <h4 className="font-semibold">{group.no_surat_jalan}</h4>
                                                <span className="text-sm text-muted-foreground">
                                                    {group.tanggal} • {group.category}
                                                </span>
                                            </div>
                                            <p className="text-sm mb-2">
                                                <strong>Client:</strong> {group.client_name}
                                                {group.destination_client && group.destination_client !== group.client_name && (
                                                    <> {" → "} {group.destination_client}</>
                                                )}
                                                {group.tujuan && group.category === 'outsourcing' && (
                                                    <> ({"("}{group.tujuan}{")"})</>
                                                )}
                                            </p>
                                            <p className="text-sm mb-2">
                                                <strong>Plat:</strong> {group.plat}
                                            </p>
                                            <div className="mt-2">
                                                <p className="text-xs font-medium mb-1">Item:</p>
                                                <ul className="list-disc list-inside text-xs space-y-1">
                                                    {group.items.map((item, i) => (
                                                        <li key={i}>
                                                            {item.jenis_barang}: {formatNumber(item.qty_barang)} pcs
                                                            {item.total_harga > 0 && ` (Rp ${formatNumber(item.total_harga)})`}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>

                            {/* Clients and Destinations */}
                            {(previewData.clients.length > 0 || previewData.destinations.length > 0) && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Client yang Ditemukan</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        {previewData.clients.length > 0 && (
                                            <div className="mb-2">
                                                <p className="text-xs font-medium mb-1">Client:</p>
                                                <div className="flex flex-wrap gap-2">
                                                    {previewData.clients.map((client, i) => (
                                                        <span key={i} className="text-xs bg-muted px-2 py-1 rounded">
                                                            {client}
                                                        </span>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                        {previewData.destinations.length > 0 && (
                                            <div>
                                                <p className="text-xs font-medium mb-1">Destination:</p>
                                                <div className="flex flex-wrap gap-2">
                                                    {previewData.destinations.map((dest, i) => (
                                                        <span key={i} className="text-xs bg-muted px-2 py-1 rounded">
                                                            {dest}
                                                        </span>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            )}

                            <DialogFooter>
                                <Button variant="outline" onClick={() => setIsPreviewDialogOpen(false)}>
                                    Batal
                                </Button>
                                <Button
                                    onClick={handleConfirmImport}
                                    disabled={importForm.processing || previewData.errors.length > 0}
                                >
                                    Import Sekarang
                                </Button>
                            </DialogFooter>
                        </div>
                    )}

                    {!previewData && (
                        <p>Muat data preview...</p>
                    )}
                </DialogContent>
            </Dialog>
        </PageShell>
    );
}

TripIndex.layout = { breadcrumbs: [{ title: 'Trip', href: tripRoutes.index.url() }] };