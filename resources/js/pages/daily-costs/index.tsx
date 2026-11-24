import { Link } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import dailyCostRoutes from '@/routes/daily-costs';
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
import { Label } from '@/components/ui/label';
import { formatDate, formatRupiah } from '@/lib/format';
import type { DailyCost, Option, Paginated } from '@/types/domain';

type ExtraItem = {
    nama_biaya: string;
    nominal: string;
};

type CostForm = {
    tanggal: string;
    no_plat: string;
    no_surat_jalan: string;
    uang_jalan: string;
    catatan: string;
    extras: ExtraItem[];
};

function currentDate(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${now.getFullYear()}-${month}-${day}`;
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
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingCost, setEditingCost] = useState<DailyCost | null>(null);

    // Form for add/edit
    const form = useForm<CostForm>({
        tanggal: currentDate(),
        no_plat: '',
        no_surat_jalan: '',
        uang_jalan: '0',
        catatan: '',
        extras: [],
    });

    const openAddDialog = () => {
        setEditingCost(null);
        form.setData({
            tanggal: currentDate(),
            no_plat: '',
            no_surat_jalan: '',
            uang_jalan: '0',
            catatan: '',
            extras: [],
        });
        setDialogOpen(true);
    };

    const openEditDialog = (cost: DailyCost) => {
        setEditingCost(cost);
        form.setData({
            tanggal: cost.tanggal ?? currentDate(),
            no_plat: cost.no_plat ?? '',
            no_surat_jalan: cost.no_surat_jalan ?? '',
            uang_jalan: String(cost.uang_jalan ?? 0),
            catatan: cost.catatan ?? '',
            extras: cost.extras?.map((e) => ({
                nama_biaya: e.nama_biaya,
                nominal: String(e.nominal ?? 0),
            })) ?? [],
        });
        setDialogOpen(true);
    };

    const submitForm = (event: React.FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setDialogOpen(false);
            },
        };

        if (editingCost) {
            form.put(dailyCostRoutes.update.url({ dailyCost: editingCost.id }), options);
        } else {
            form.post(dailyCostRoutes.store.url(), options);
        }
    };

    const addExtra = () => {
        form.setData('extras', [...form.data.extras, { nama_biaya: '', nominal: '' }]);
    };

    const removeExtra = (index: number) => {
        const newExtras = form.data.extras.filter((_, i) => i !== index);
        form.setData('extras', newExtras);
    };

    const updateExtra = (index: number, field: 'nama_biaya' | 'nominal', value: string) => {
        const newExtras = form.data.extras.map((item, i) =>
            i === index ? { ...item, [field]: value } : item
        );
        form.setData('extras', newExtras);
    };

    return (
        <PageShell
            title="Biaya Harian"
            description="Uang jalan per hari per plat"
            actions={
                <>
                    <Button onClick={openAddDialog}>
                        Tambah Biaya Harian
                    </Button>
                    <Button onClick={() => { window.location.href = dailyCostRoutes.template.url(); }}>
                        Unduh Template
                    </Button>
                    <Button asChild variant="outline">
                        <Link href={dailyCostRoutes.import.url()}>
                            Import Biaya Harian
                        </Link>
                    </Button>
                </>
            }
        >
            <FilterBar
                path={dailyCostRoutes.index.url()}
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
                        <Th>Keterangan</Th>
                        <Th />
                    </tr>
                </thead>
                <tbody>
                    {dailyCosts.data.length === 0 && <EmptyRow colSpan={6} />}
                    {dailyCosts.data.map((cost) => (
                        <tr key={cost.id}>
                            <Td>{formatDate(cost.tanggal)}</Td>
                            <Td>{cost.no_plat}</Td>
                            <Td>{cost.no_surat_jalan}</Td>
                            <Td right>{formatRupiah(cost.uang_jalan)}</Td>
                            <Td>
                                {cost.extras && cost.extras.length > 0 ? (
                                    <ul className="list-disc list-inside text-xs">
                                        {cost.extras.map((extra, idx) => (
                                            <li key={idx}>
                                                {extra.nama_biaya}: {formatRupiah(extra.nominal)}
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <span className="text-gray-400 text-xs">-</span>
                                )}
                            </Td>
                            <Td>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => openEditDialog(cost)}
                                >
                                    Ubah
                                </Button>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    className="ml-2"
                                    onClick={() =>
                                        confirmDelete(
                                            dailyCostRoutes.destroy.url({ dailyCost: cost.id }),
                                            "Hapus biaya harian ini?",
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

            {/* Add/Edit Dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editingCost ? "Ubah Biaya Harian" : "Tambah Biaya Harian"}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitForm} className="grid gap-3">
                        <Field label="Tanggal" error={form.errors.tanggal}>
                            <Input
                                type="date"
                                value={form.data.tanggal}
                                onChange={(e) => form.setData("tanggal", e.target.value)}
                            />
                        </Field>
                        <Field label="No. Plat" error={form.errors.no_plat}>
                            <Input
                                list="daily-cost-plates"
                                placeholder="Pilih atau ketik plat"
                                value={form.data.no_plat}
                                onChange={(e) => form.setData('no_plat', e.target.value)}
                            />
                            <datalist id="daily-cost-plates">
                                {vehicles.map((vehicle) => (
                                    <option key={vehicle.id} value={vehicle.plat} />
                                ))}
                            </datalist>
                        </Field>
                        <Field label="No. Surat Jalan" error={form.errors.no_surat_jalan}>
                            <Input
                                value={form.data.no_surat_jalan}
                                onChange={(e) => form.setData('no_surat_jalan', e.target.value)}
                            />
                        </Field>
                        <Field label="Uang Jalan (Rp)" error={form.errors.uang_jalan}>
                            <Input
                                type="number"
                                min="0"
                                step="1"
                                value={form.data.uang_jalan}
                                onChange={(e) => form.setData('uang_jalan', e.target.value)}
                            />
                        </Field>
                        <Field label="Keterangan" error={form.errors.catatan}>
                            <Input
                                value={form.data.catatan}
                                onChange={(e) => form.setData('catatan', e.target.value)}
                            />
                        </Field>

                        {/* Extras */}
                        <div className="mt-2">
                            <Label>Biaya Lainnya (opsional)</Label>
                            {form.data.extras.map((extra, index) => (
                                <div key={index} className="flex gap-2 mt-2">
                                    <div className="flex-1">
                                        <Input
                                            placeholder="Nama biaya"
                                            value={extra.nama_biaya}
                                            onChange={(e) => updateExtra(index, 'nama_biaya', e.target.value)}
                                        />
                                    </div>
                                    <div className="w-32">
                                        <Input
                                            type="number"
                                            min="0"
                                            placeholder="Nominal"
                                            value={extra.nominal}
                                            onChange={(e) => updateExtra(index, 'nominal', e.target.value)}
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => removeExtra(index)}
                                        disabled={form.data.extras.length === 1}
                                    >
                                        ✕
                                    </Button>
                                </div>
                            ))}
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="mt-2"
                                onClick={addExtra}
                            >
                                + Tambah Biaya Lain
                            </Button>
                        </div>

                        <Button type="submit" disabled={form.processing}>
                            Simpan
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>
        </PageShell>
    );
}

DailyCostIndex.layout = { breadcrumbs: [{ title: 'Biaya Harian', href: dailyCostRoutes.index.url() }] };
