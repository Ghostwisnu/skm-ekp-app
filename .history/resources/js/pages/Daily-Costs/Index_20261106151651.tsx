import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    store as storeDailyCost,
    update as updateDailyCost,
} from '@/actions/App/Http/Controllers/DailyCostController';
import { FilterBar } from '@/components/filter-bar';
import {
    DataTable,
    EmptyRow,
    Field,
    PageShell,
    Td,
    Th,
} from '@/components/page-shell';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { formatDate, formatRupiah } from '@/lib/format';
import type { DailyCost, Option, Paginated } from '@/types/domain';

type CostForm = { trip_id: number } & Record<CostKey, string>;

function emptyForm(trip: Trip): CostForm {
    const base = { trip_id: trip.id } as CostForm;

    costComponents.forEach(([key]) => {
        base[key] = String(trip.daily_cost?.[key] ?? 0);
    });

    return base;
}

function CostDialog({ trip, onClose }: { trip: Trip; onClose: () => void }) {
    const form = useForm<CostForm>(emptyForm(trip));
    const errors = form.errors as Record<string, string | undefined>;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (trip.daily_cost) {
            form.put(`/daily-costs/${trip.daily_cost.id}`, options);
        } else {
            form.post('/daily-costs', options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Biaya {trip.vehicle?.plat} • {formatDate(trip.tanggal)}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-3">
                    {costComponents.map(([key, label]) => (
                        <Field
                            key={key}
                            label={`${label} (Rp)`}
                            error={errors[key]}
                        >
                            <Input
                                type="number"
                                min="0"
                                step="1"
                                value={form.data[key]}
                                onChange={(e) =>
                                    form.setData(key, e.target.value)
                                }
                            />
                        </Field>
                    ))}
                    <Button type="submit" disabled={form.processing}>
                        Simpan
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function DailyCostIndex({
    trips,
    filters,
    vehicles,
}: {
    trips: Paginated<Trip>;
    filters: Record<string, string>;
    vehicles: Option[];
}) {
    const [editing, setEditing] = useState<Trip | null>(null);

    return (
        <PageShell
            title="Biaya Harian"
            description="Biaya per trip (per hari per plat)"
        >
            <FilterBar
                path="/daily-costs"
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
                        <Th>Tanggal</Th>
                        <Th>Plat</Th>
                        <Th>No. Surat Jalan</Th>
                        {costComponents.map(([key, label]) => (
                            <Th key={key} right>
                                {label}
                            </Th>
                        ))}
                        <Th right>Total</Th>
                        <Th />
                    </tr>
                </thead>
                <tbody>
                    {trips.data.length === 0 && <EmptyRow colSpan={11} />}
                    {trips.data.map((trip) => (
                        <tr key={trip.id}>
                            <Td>{formatDate(trip.tanggal)}</Td>
                            <Td>{trip.vehicle?.plat}</Td>
                            <Td>{trip.no_surat_jalan}</Td>
                            {costComponents.map(([key]) => (
                                <Td key={key} right>
                                    {formatRupiah(trip.daily_cost?.[key] ?? 0)}
                                </Td>
                            ))}
                            <Td right className="font-semibold">
                                {formatRupiah(trip.daily_cost?.total ?? 0)}
                            </Td>
                            <Td>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setEditing(trip)}
                                >
                                    {trip.daily_cost ? 'Ubah' : 'Isi'}
                                </Button>
                            </Td>
                        </tr>
                    ))}
                </tbody>
            </DataTable>
            <Pagination paginator={trips} />
            {editing && (
                <CostDialog
                    key={editing.id}
                    trip={editing}
                    onClose={() => setEditing(null)}
                />
            )}
        </PageShell>
    );
}

DailyCostIndex.layout = {
    breadcrumbs: [{ title: 'Biaya Harian', href: '/daily-costs' }],
};
