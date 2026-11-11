import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { confirmDelete } from '@/components/confirm-delete';
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

type CostForm = {
    tanggal: string;
    no_plat: string;
    no_surat_jalan: string;
    uang_jalan: string;
};

function currentDate(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

function CostDialog({
    cost,
    vehicles,
    onClose,
}: {
    cost: DailyCost | null;
    vehicles: Option[];
    onClose: () => void;
}) {
    const form = useForm<CostForm>({
        tanggal: cost?.tanggal ?? currentDate(),
        no_plat: cost?.no_plat ?? '',
        no_surat_jalan: cost?.no_surat_jalan ?? '',
        uang_jalan: String(cost?.uang_jalan ?? 0),
    });
    const errors = form.errors as Record<string, string | undefined>;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (cost) {
            form.put(`/daily-costs/${cost.id}`, options);
        } else {
            form.post('/daily-costs', options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {cost ? 'Ubah Biaya Harian' : 'Tambah Biaya Harian'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-3">
                    <Field label="Tanggal" error={errors.tanggal}>
                        <Input
                            type="date"
                            value={form.data.tanggal}
                            onChange={(e) =>
                                form.setData('tanggal', e.target.value)
                            }
                        />
                    </Field>
                    <Field label="No. Plat" error={errors.no_plat}>
                        <Input
                            list="daily-cost-plates"
                            placeholder="Pilih atau ketik plat"
                            value={form.data.no_plat}
                            onChange={(e) =>
                                form.setData('no_plat', e.target.value)
                            }
                        />
                        <datalist id="daily-cost-plates">
                            {vehicles.map((vehicle) => (
                                <option key={vehicle.id} value={vehicle.plat} />
                            ))}
                        </datalist>
                    </Field>
                    <Field
                        label="No. Surat Jalan"
                        error={errors.no_surat_jalan}
                    >
                        <Input
                            value={form.data.no_surat_jalan}
                            onChange={(e) =>
                                form.setData('no_surat_jalan', e.target.value)
                            }
                        />
                    </Field>
                    <Field label="Uang Jalan (Rp)" error={errors.uang_jalan}>
                        <Input
                            type="number"
                            min="0"
                            step="1"
                            value={form.data.uang_jalan}
                            onChange={(e) =>
                                form.setData('uang_jalan', e.target.value)
                            }
                        />
                    </Field>
                    <Button type="submit" disabled={form.processing}>
                        Simpan
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function DailyCostIndex({
    dailyCosts,
    filters,
    vehicles,
}: {
    dailyCosts: Paginated<DailyCost>;
    filters: Record<string, string>;
    vehicles: Option[];
}) {
    const [dialog, setDialog] = useState<{ cost: DailyCost | null } | null>(
        null,
    );

    return (
        <PageShell
            title="Biaya Harian"
            description="Uang jalan per hari per plat"
            actions={
                <Button onClick={() => setDialog({ cost: null })}>
                    Tambah Biaya Harian
                </Button>
            }
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
                        <Th right>Uang Jalan</Th>
                        <Th />
                    </tr>
                </thead>
                <tbody>
                    {dailyCosts.data.length === 0 && <EmptyRow colSpan={5} />}
                    {dailyCosts.data.map((cost) => (
                        <tr key={cost.id}>
                            <Td>{formatDate(cost.tanggal)}</Td>
                            <Td>{cost.no_plat}</Td>
                            <Td>{cost.no_surat_jalan}</Td>
                            <Td right>{formatRupiah(cost.uang_jalan)}</Td>
                            <Td>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setDialog({ cost })}
                                >
                                    Ubah
                                </Button>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    className="ml-2"
                                    onClick={() =>
                                        confirmDelete(
                                            `/daily-costs/${cost.id}`,
                                            'Hapus biaya harian ini?',
                                        )
                                    }
                                >
                                    Hapus
                                </Button>
                            </Td>
                        </tr>
                    ))}
                </tbody>
            </DataTable>
            <Pagination paginator={dailyCosts} />
            {dialog && (
                <CostDialog
                    key={dialog.cost?.id ?? 'new'}
                    cost={dialog.cost}
                    vehicles={vehicles}
                    onClose={() => setDialog(null)}
                />
            )}
        </PageShell>
    );
}

DailyCostIndex.layout = {
    breadcrumbs: [{ title: 'Biaya Harian', href: '/daily-costs' }],
};
