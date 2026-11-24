import { Link, router } from '@inertiajs/react';
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
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, formatRupiah } from '@/lib/format';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { DailyCost, Option, Paginated } from '@/types/domain';

type DailyCostPreview = {
    rows: Array<{
        tanggal: string;
        plat: string;
        no_surat_jalan: string;
        uang_jalan: number;
        catatan: string;
        extras: Array<{ nama_biaya: string; nominal: number }>;
    }>;
    total: number;
    totalUangJalan: number;
    errors: string[];
    plats: string[];
};

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
        catatan: cost?.catatan ?? '',
        extras: cost?.extras?.map((e) => ({
            nama_biaya: e.nama_biaya,
            nominal: String(e.nominal ?? 0),
        })) ?? [],
    });
    const errors = form.errors as Record<string, string | undefined>;

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

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (cost) {
            form.put(dailyCostRoutes.update.url({ dailyCost: cost.id }), options);
        } else {
            form.post(dailyCostRoutes.store.url(), options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{cost ? "Ubah Biaya Harian" : "Tambah Biaya Harian"}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-3">
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
                    <Field label="No. Surat Jalan" error={form.errors.no_surat_jalan}>
                        <Input
                            value={form.data.no_surat_jalan}
                            onChange={(e) =>
                                form.setData('no_surat_jalan', e.target.value)
                            }
                        />
                    </Field>
                    <Field label="Uang Jalan (Rp)" error={form.errors.uang_jalan}>
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
                    <Field label="Keterangan" error={form.errors.catatan}>
                        <Input
                            value={form.data.catatan}
                            onChange={(e) =>
                                form.setData('catatan', e.target.value)
                            }
                        />
                    </Field>

                    {/* Biaya Lainnya */}
                    <div className="mt-2">
                        <label className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                            Biaya Lainnya (opsional)
                        </label>
                        {form.data.extras.map((extra, index) => (
                            <div key={index} className="flex gap-2 mt-2">
                                <div className="flex-1">
                                    <Input
                                        placeholder="Nama biaya"
                                        value={extra.nama_biaya}
                                        onChange={(e) =>
                                            updateExtra(index, 'nama_biaya', e.target.value)
                                        }
                                    />
                                </div>
                                <div className="w-32">
                                    <Input
                                        type="number"
                                        min="0"
                                        placeholder="Nominal"
                                        value={extra.nominal}
                                        onChange={(e) =>
                                            updateExtra(index, 'nominal', e.target.value)
                                        }
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
    );
}

function PpnForm({ ppnDefault }: { ppnDefault: number }) {
    const form = useForm({ ppn_default: String(ppnDefault) });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.put("/admin/settings", { preserveScroll: true });
            }}
            className="flex max-w-sm items-end gap-3 rounded-md border bg-card p-3"
        >
            <Field label="PPN default (%)" error={form.errors.ppn_default}>
                <Input
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    value={form.data.ppn_default}
                    onChange={(e) => form.setData("ppn_default", e.target.value)}
                />
            </Field>
            <Button type="submit" disabled={form.processing}>
                Simpan
            </Button>
        </form>
    );
}

type CompanyDetails = {
    company_name: string;
    company_address: string;
    company_bank_name: string;
    company_bank_account: string;
};

function CompanyDetailsForm({ companyDetails }: { companyDetails: CompanyDetails }) {
    const form = useForm(companyDetails);

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.put("/admin/settings", { preserveScroll: true });
            }}
            className="grid max-w-2xl gap-3 rounded-md border bg-card p-4"
        >
            <h2 className="text-sm font-semibold">Identitas Perusahaan Pemilik</h2>
            <Field label="Nama perusahaan" error={form.errors.company_name}>
                <Input
                    value={form.data.company_name}
                    onChange={(event) => form.setData("company_name", event.target.value)}
                />
            </Field>
            <Field label="Alamat perusahaan" error={form.errors.company_address}>
                <Input
                    value={form.data.company_address}
                    onChange={(event) => form.setData("company_address", event.target.value)}
                />
            </Field>
            <Field label="Nama bank pembayaran" error={form.errors.company_bank_name}>
                <Input
                    value={form.data.company_bank_name}
                    onChange={(event) => form.setData("company_bank_name", event.target.value)}
                />
            </Field>
            <Field label="Nomor rekening" error={form.errors.company_bank_account}>
                <Input
                    value={form.data.company_bank_account}
                    onChange={(event) => form.setData("company_bank_account", event.target.value)}
                />
            </Field>
            <Button type="submit" disabled={form.processing}>
                Simpan Identitas Perusahaan
            </Button>
        </form>
    );
}

export default function DailyCostIndex({
    dailyCosts,
    filters,
    vehicles,
    ppnDefault,
    companyDetails,
}: {
    dailyCosts: Paginated<DailyCost>;
    filters: Record<string, string>;
    vehicles: Option[];
    ppnDefault: number;
    companyDetails: CompanyDetails;
}) {
    const { auth } = usePage<{ auth: { user: { id: number } } }>().props;
    const [dialog, setDialog] = useState<{ cost: DailyCost | null } | null>(null);
    const [isImportDialogOpen, setIsImportDialogOpen] = useState(false);
    const [isPreviewDialogOpen, setIsPreviewDialogOpen] = useState(false);
    const [previewData, setPreviewData] = useState<DailyCostPreview | null>(null);
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
                dailyCostRoutes.preview.url(),
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
            router.post(dailyCostRoutes.import.url(), formData, {
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
            router.post(dailyCostRoutes.import.url(), formData, {
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
            title="Biaya Harian"
            description="Uang jalan per hari per plat"
            actions={
                <>
                    <Button onClick={() => setDialog({ cost: null })}>
                        Tambah Biaya Harian
                    </Button>
                    <a href={dailyCostRoutes.template.url()} download className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2">
                        Unduh Template
                    </a>
                    <Button onClick={() => setIsImportDialogOpen(true)} variant="outline">
                        Import Biaya Harian
                    </Button>
                </>
            }
        >
            <PpnForm ppnDefault={ppnDefault} />
            <CompanyDetailsForm companyDetails={companyDetails} />
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
                    {dailyCosts.data.map((cost) => {
                        const role = getUserRole(cost);
                        return (
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
                                                dailyCostRoutes.destroy.url({ dailyCost: cost.id }),
                                                "Hapus biaya harian ini?",
                                            )
                                        }
                                    >
                                        Hapus
                                    </Button>
                                </Td>
                            </tr>
                        );
                    })}
                </tbody>
            </DataTable>
            <Pagination paginator={dailyCosts} />

            {/* Import Dialog */}
            <Dialog open={isImportDialogOpen} onOpenChange={setIsImportDialogOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Import Biaya Harian</DialogTitle>
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
                <DialogContent className="max-w-3xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Preview Data Import Biaya Harian</DialogTitle>
                    </DialogHeader>

                    {previewData && (
                        <div className="space-y-4">
                            {/* Summary */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Ringkasan</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-3 gap-4">
                                        <div>
                                            <p className="text-sm text-muted-foreground">Total Baris</p>
                                            <p className="text-xl font-bold">{previewData.total}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">Total Uang Jalan</p>
                                            <p className="text-xl font-bold">{formatRupiah(previewData.totalUangJalan)}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">Unik Plat</p>
                                            <p className="text-xl font-bold">{previewData.plats.length}</p>
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

                            {/* Preview Table */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Data yang Akan Diimpor ({previewData.rows.length} Baris)</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {previewData.rows.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">Tidak ada data untuk ditampilkan.</p>
                                    ) : (
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-sm">
                                                <thead>
                                                    <tr className="border-b">
                                                        <th className="px-3 py-2 text-left">Tanggal</th>
                                                        <th className="px-3 py-2 text-left">Plat</th>
                                                        <th className="px-3 py-2 text-left">No. Surat Jalan</th>
                                                        <th className="px-3 py-2 text-right">Uang Jalan</th>
                                                        <th className="px-3 py-2 text-left">Catatan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {previewData.rows.map((row, index) => (
                                                        <tr key={index} className="border-b last:border-0">
                                                            <td className="px-3 py-2">{row.tanggal}</td>
                                                            <td className="px-3 py-2 font-mono">{row.plat}</td>
                                                            <td className="px-3 py-2">{row.no_surat_jalan}</td>
                                                            <td className="px-3 py-2 text-right">{formatRupiah(row.uang_jalan)}</td>
                                                            <td className="px-3 py-2">{row.catatan || '-'}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}

                                    {/* Extras Detail */}
                                    {previewData.rows.some(r => r.extras && r.extras.length > 0) && (
                                        <div className="mt-4">
                                            <p className="text-xs font-medium mb-2">Biaya Lainnya (Extras):</p>
                                            <div className="border rounded p-3">
                                                {previewData.rows.map((row, i) => {
                                                    const extras = row.extras || [];
                                                    if (extras.length === 0) return null;
                                                    return (
                                                        <div key={i} className="mb-2">
                                                            <p className="text-xs text-muted-foreground">
                                                                {row.plat} - {row.no_surat_jalan}
                                                            </p>
                                                            <ul className="list-disc list-inside text-xs ml-4">
                                                                {extras.map((e, j) => (
                                                                    <li key={j}>
                                                                        {e.nama_biaya}: {formatRupiah(e.nominal)}
                                                                    </li>
                                                                ))}
                                                            </ul>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Vehicles */}
                            {previewData.plats.length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Plat yang Ditemukan</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="flex flex-wrap gap-2">
                                            {previewData.plats.map((plat, i) => (
                                                <span key={i} className="text-xs bg-muted px-2 py-1 rounded">
                                                    {plat}
                                                </span>
                                            ))}
                                        </div>
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

            <CostDialog
                key={dialog?.cost?.id ?? 'new'}
                cost={dialog?.cost ?? null}
                vehicles={vehicles}
                onClose={() => setDialog(null)}
            />
        </PageShell>
    );
}

DailyCostIndex.layout = { breadcrumbs: [{ title: 'Biaya Harian', href: dailyCostRoutes.index.url() }] };

// Helper function (moved from file to avoid duplication)
function getUserRole(user): string {
    // This is a placeholder - the actual implementation is in users.tsx
    return 'user';
}